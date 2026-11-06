"""Backup contract tests: fake MySQL/R2, real gzip/ZIP/SHA-256, no network."""
import gzip
import json
import os
from pathlib import Path
import shlex
import stat
import subprocess
import tempfile
import unittest
import zipfile


SCRIPT = Path(__file__).resolve().parents[2] / "scripts/backup-to-r2.sh"

AWS_FAKE = r'''#!/usr/bin/env python3
import hashlib, json, os, pathlib, shutil, sys
args = sys.argv[1:]
if args == ['--version']:
    print('aws-cli/2.31.0 fake'); sys.exit(0)
root = pathlib.Path(os.environ['FAKE_R2'])
with (root / 'calls').open('a') as f: f.write(json.dumps(args) + '\n')
assert args[:6] == ['--endpoint-url', 'https://fixture.r2.cloudflarestorage.com', '--region', 'auto', '--no-cli-pager', 's3'] or args[:6] == ['--endpoint-url', 'https://fixture.r2.cloudflarestorage.com', '--region', 'auto', '--no-cli-pager', 's3api']
assert os.environ['AWS_ACCESS_KEY_ID'] == 'fixture-key'
assert os.environ['AWS_SECRET_ACCESS_KEY'] == 'fixture-secret'
assert os.environ['AWS_EC2_METADATA_DISABLED'] == 'true'
mode = os.environ.get('FAKE_FAIL', '')
if args[5:7] == ['s3', 'cp']:
    source, target = args[7:9]
    if not source.startswith('s3://'):
        assert target.startswith('s3://private-backups/eduan/')
        assert args[9:11] == ['--only-show-errors', '--metadata']
        if mode == 'upload': sys.exit(41)
        shutil.copyfile(source, root / 'object')
        (root / 'metadata').write_text(args[11].split('=', 1)[1])
        (root / 'key').write_text(target)
    else:
        assert target == '-'
        assert source == (root / 'key').read_text()
        if mode == 'readback': sys.exit(42)
        payload = (root / 'object').read_bytes()
        sys.stdout.buffer.write(payload + (b'corrupt' if mode == 'content' else b''))
elif args[5:7] == ['s3api', 'head-object']:
    if mode == 'head': sys.exit(43)
    size = (root / 'object').stat().st_size
    digest = (root / 'metadata').read_text()
    if mode == 'size': size += 1
    if mode == 'metadata': digest = '0' * 64
    print(str(size) + '\t' + digest)
else:
    raise AssertionError(args)
'''

MYSQL_FAKE = r'''#!/usr/bin/env python3
import json, os, pathlib, stat, sys
args = sys.argv[1:]
assert args[0].startswith('--defaults-file=')
config = pathlib.Path(args[0].split('=', 1)[1])
assert stat.S_IMODE(config.stat().st_mode) == 0o600
assert os.environ['MYSQL_TEST_LOGIN_FILE'] == str(config.parent / 'unused-login.cnf')
assert 'MYSQL_PWD' not in os.environ
operands = []
options_ended = False
for arg in args:
    if arg == '--' and not options_ended:
        options_ended = True
    elif not options_ended and arg.startswith('-'):
        continue
    else:
        operands.append(arg)
database = os.environ.get('FAKE_DB_NAME', 'fixture_db')
schema_only = '--no-data' in args
expected_operands = [database, 'logs'] if schema_only else [database]
if operands != expected_operands and len(operands) > 1:
    sys.stderr.write('mysqldump: Couldn\'t find table: "' + operands[1] + '"\n')
    sys.exit(45)
assert operands == expected_operands
assert all(flag in args for flag in ['--single-transaction', '--quick', '--skip-lock-tables', '--no-tablespaces', '--routines', '--events', '--triggers', '--set-gtid-purged=OFF'])
assert 'password=' in config.read_text()
if 'FAKE_DB_PASSWORD' in os.environ:
    escaped = os.environ['FAKE_DB_PASSWORD'].replace('\\', '\\\\').replace('"', '\\"')
    assert ('password="' + escaped + '"\n') in config.read_text()
pathlib.Path(os.environ['FAKE_R2'], 'dump_args').write_text(json.dumps(args))
with pathlib.Path(os.environ['FAKE_R2'], 'dump_calls').open('a') as f:
    f.write(json.dumps(args) + '\n')
if os.environ.get('FAKE_FAIL') == 'empty': sys.exit(0)
if schema_only:
    assert '--skip-routines' in args and '--skip-events' in args
    if os.environ.get('FAKE_FAIL') == 'schema': sys.exit(46)
    print('CREATE TABLE `logs` (id int, payload text);')
    print('CREATE TRIGGER logs_fixture BEFORE INSERT ON `logs` FOR EACH ROW SET NEW.id = NEW.id;')
else:
    print('-- fixture SQL\nCREATE TABLE fixture (id int);\nINSERT INTO fixture VALUES (1);')
    if ('--ignore-table=' + database + '.logs') not in args:
        print('CREATE TABLE `logs` (id int, payload text);')
        print("INSERT INTO `logs` VALUES (1, 'large-log-payload');")
    print('CREATE PROCEDURE fixture_routine() SELECT 1;')
    print('CREATE EVENT fixture_event ON SCHEDULE EVERY 1 DAY DO SELECT 1;')
if os.environ.get('FAKE_FAIL') == 'dump': sys.exit(44)
'''


class BackupTest(unittest.TestCase):
    def setUp(self):
        self.temp = tempfile.TemporaryDirectory(prefix="eduan-backup-test-")
        self.addCleanup(self.temp.cleanup)
        self.root = Path(self.temp.name)
        self.project = self.root / "Laravel project"
        self.project.mkdir()
        (self.project / "artisan").write_text("not executed")
        (self.project / "public/uploads").mkdir(parents=True)
        (self.project / "public/uploads/photo.txt").write_text("upload fixture")
        (self.project / ".git").mkdir()
        (self.project / ".git/fixture").write_text("hidden fixture")
        outside = self.root / "outside.txt"
        outside.write_text("must not traverse")
        (self.project / "external-link").symlink_to(outside)
        self.work = self.root / "private backups"
        self.remote = self.root / "fake-r2"
        self.remote.mkdir()
        self.bin = self.root / "bin"
        self.bin.mkdir()
        for name, code in [("aws", AWS_FAKE), ("mysqldump", MYSQL_FAKE)]:
            path = self.bin / name
            path.write_text(code)
            path.chmod(0o700)
        self.env = os.environ.copy()
        self.env.update(PATH=str(self.bin) + os.pathsep + self.env["PATH"],
                        FAKE_R2=str(self.remote), MYSQL_PWD="inherited-wrong-password")
        self.write_env()

    def write_env(self, extra="", work=None, password=None):
        password = password if password is not None else "'literal # ${not_expanded} $(false) `false` \\\"'"
        self.env_text = (
            "DB_CONNECTION=mysql\nDB_HOST=127.0.0.1\nDB_PORT=3306\n"
            "DB_DATABASE=fixture_db\nDB_USERNAME=fixture_user\n"
            f"DB_PASSWORD={password}\nAWS_ACCESS_KEY_ID=fixture-key\n"
            "AWS_SECRET_ACCESS_KEY=fixture-secret\n"
            "AWS_ENDPOINT=https://fixture.r2.cloudflarestorage.com\n"
            "BACKUP_R2_BUCKET=private-backups\nBACKUP_R2_PREFIX=eduan\n"
            f'BACKUP_WORK_DIR="{work or self.work}"\n' + extra
        )
        (self.project / ".env").write_text(self.env_text)

    def run_script(self, mode="db", check=False, fail=""):
        args = ["bash", str(SCRIPT), mode, "--project-dir", str(self.project)]
        if check:
            args.append("--check")
        env = self.env | {"FAKE_FAIL": fail}
        return subprocess.run(args, env=env, text=True, capture_output=True, timeout=20)

    def assert_preserved(self, result, suffix):
        self.assertNotEqual(result.returncode, 0, result.stdout)
        files = list(self.work.glob(f"run.*/*{suffix}"))
        self.assertEqual(len(files), 1, result.stderr)
        self.assertFalse(list(self.work.glob("run.*/mysql.cnf")))
        self.assertFalse((self.work / ".backup.lock").exists())
        self.assertNotIn("fixture-secret", result.stdout + result.stderr)
        self.assertEqual(stat.S_IMODE(files[0].stat().st_mode), 0o600)
        return files[0]

    def test_check_has_no_files_or_transfer(self):
        for mode in ("db", "project"):
            with self.subTest(mode=mode):
                result = self.run_script(mode, check=True)
                self.assertEqual(result.returncode, 0, result.stderr)
        self.assertFalse(self.work.exists())
        self.assertFalse((self.remote / "calls").exists())

    def test_db_success_verifies_and_removes_only_generated_archive(self):
        self.work.mkdir(mode=0o700)
        old = self.work / "previous.sql.gz"
        old.write_bytes(b"existing archive")
        result = self.run_script()
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertIn("CREATE TABLE fixture", gzip.decompress((self.remote / "object").read_bytes()).decode())
        self.assertEqual(list(self.work.iterdir()), [old])
        calls = (self.remote / "calls").read_text().splitlines()
        self.assertEqual(len(calls), 3)  # upload, head, full readback
        self.assertNotIn("literal #", (self.remote / "dump_args").read_text())

    def test_configured_public_html_supports_both_modes_and_preserves_uploads(self):
        (self.project / "public").rename(self.project / "public_html")
        self.write_env(extra='APP_PUBLIC_PATH="public_html"\n')
        for mode in ("db", "project"):
            with self.subTest(mode=mode):
                result = self.run_script(mode, check=True)
                self.assertEqual(result.returncode, 0, result.stderr)
        self.assertFalse(self.work.exists())
        self.assertFalse((self.remote / "calls").exists())
        for mode in ("db", "project"):
            with self.subTest(mode=mode):
                result = self.run_script(mode)
                self.assertEqual(result.returncode, 0, result.stderr)
        with zipfile.ZipFile(self.remote / "object") as archive:
            self.assertEqual(archive.read("public_html/uploads/photo.txt"), b"upload fixture")
            self.assertEqual(archive.read(".env").decode(), self.env_text)
            self.assertFalse(any(name.startswith("public/") for name in archive.namelist()))

    def test_public_path_uses_project_dotenv_or_public_default(self):
        self.env["APP_PUBLIC_PATH"] = "inherited-missing-directory"
        for extra in ("", "APP_PUBLIC_PATH=public\n", "APP_PUBLIC_PATH=\n"):
            with self.subTest(extra=extra):
                self.write_env(extra=extra)
                for mode in ("db", "project"):
                    result = self.run_script(mode, check=True)
                    self.assertEqual(result.returncode, 0, result.stderr)
        self.assertFalse(self.work.exists())
        self.assertFalse((self.remote / "calls").exists())

    def test_missing_configured_public_directory_cannot_start_backup(self):
        self.write_env(extra="APP_PUBLIC_PATH=public_html\n")
        for mode in ("db", "project"):
            for check in (False, True):
                with self.subTest(mode=mode, check=check):
                    result = self.run_script(mode, check=check)
                    self.assertNotEqual(result.returncode, 0)
                    self.assertIn("APP_PUBLIC_PATH", result.stderr)
        self.assertFalse(self.work.exists())
        self.assertFalse((self.remote / "dump_args").exists())
        self.assertFalse((self.remote / "calls").exists())

    def test_project_zip_contains_hidden_files_uploads_and_symlinks(self):
        result = self.run_script("project")
        self.assertEqual(result.returncode, 0, result.stderr)
        with zipfile.ZipFile(self.remote / "object") as archive:
            self.assertEqual(archive.read(".env").decode(), self.env_text)
            self.assertEqual(archive.read(".git/fixture"), b"hidden fixture")
            self.assertEqual(archive.read("public/uploads/photo.txt"), b"upload fixture")
            link = archive.getinfo("external-link")
            self.assertTrue(stat.S_ISLNK(link.external_attr >> 16))
            self.assertNotIn("outside.txt", archive.namelist())
        self.assertEqual(list(self.work.iterdir()), [])
        self.assertIn("/project/", (self.remote / "key").read_text())

    def test_dump_failure_and_empty_dump_never_upload(self):
        for failure in ("dump", "empty"):
            with self.subTest(failure=failure):
                result = self.run_script(fail=failure)
                self.assertNotEqual(result.returncode, 0)
                self.assertFalse((self.remote / "calls").exists())
                self.assertFalse(list(self.work.glob("run.*/mysql.cnf")))
                self.assertFalse((self.work / ".backup.lock").exists())
        self.assertEqual(len(list(self.work.glob("run.*/*.sql.gz"))), 2)

    def test_transfer_and_verification_errors_keep_archive(self):
        # Each independent run gets a new private directory; old failures stay.
        for failure in ("upload", "head", "size", "metadata", "readback", "content"):
            with self.subTest(failure=failure):
                self.work = self.root / ("work-" + failure)
                self.write_env()
                self.assert_preserved(self.run_script(fail=failure), ".sql.gz")

    def test_project_upload_error_keeps_valid_zip(self):
        file = self.assert_preserved(self.run_script("project", fail="upload"), ".zip")
        with zipfile.ZipFile(file) as archive:
            self.assertIsNone(archive.testzip())

    def test_invalid_or_project_local_work_dir_cannot_mutate(self):
        cases = [dict(work=self.project / "public/backups"),
                 dict(extra="BACKUP_R2_BUCKET=\n"),
                 dict(extra="DB_URL=mysql://other-server\n"),
                 dict(extra="DB_SOCKET=/other/socket\n"),
                 dict(extra="DB_PORT=70000\n"),
                 dict(extra='BACKUP_R2_PREFIX="${APP_NAME}"\n')]
        for case in cases:
            with self.subTest(case=case):
                self.write_env(**case)
                self.assertNotEqual(self.run_script().returncode, 0)
        self.assertFalse(self.work.exists())
        self.assertFalse((self.project / "public/backups").exists())
        self.assertFalse((self.remote / "calls").exists())

    def test_existing_lock_is_preserved_without_dump_or_upload(self):
        self.work.mkdir(mode=0o700)
        lock = self.work / ".backup.lock"
        lock.mkdir(mode=0o700)
        (lock / "pid").write_text("existing job")
        result = self.run_script()
        self.assertNotEqual(result.returncode, 0)
        self.assertEqual((lock / "pid").read_text(), "existing job")
        self.assertFalse((self.remote / "calls").exists())
        self.assertFalse((self.remote / "dump_args").exists())

    def test_literal_dotenv_cannot_execute_shell(self):
        marker = self.root / "injection-marker"
        self.write_env(password=f"'$(touch {marker}) `touch {marker}` # ${{literal}}'")
        result = self.run_script()
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertFalse(marker.exists())
        self.write_env(password="null")
        self.assertEqual(self.run_script(check=True).returncode, 0)

    def test_permissions_symlink_and_source_guard(self):
        self.work.mkdir(mode=0o755)
        self.assertNotEqual(self.run_script().returncode, 0)
        self.work.rmdir()
        self.work.symlink_to(self.remote, target_is_directory=True)
        self.assertNotEqual(self.run_script().returncode, 0)
        # Sourcing must reject before changing caller flags or umask.
        result = subprocess.run(["bash", "-c", 'before=$(umask); source "$1"; rc=$?; [[ $rc == 1 && $(umask) == "$before" ]]', "test", str(SCRIPT)], capture_output=True, text=True)
        self.assertEqual(result.returncode, 0, result.stderr)

    def test_double_quoted_password_escape_contract(self):
        self.write_env(password=r'"literal # space $ \" \\ end"')
        self.env["FAKE_DB_PASSWORD"] = 'literal # space $ " \\ end'
        result = self.run_script()
        self.assertEqual(result.returncode, 0, result.stderr)
        for password in (r'"bad\t"', r'"bad\q"'):
            with self.subTest(password=password):
                self.write_env(password=password)
                self.assertNotEqual(self.run_script(check=True).returncode, 0)

    def test_siteground_wrapper_appended_option_is_not_a_table(self):
        real = self.bin / "mysqldump-real"
        (self.bin / "mysqldump").rename(real)
        wrapper = self.bin / "mysqldump"
        wrapper.write_text('#!/bin/sh\nexec ' + shlex.quote(str(real)) + ' "$@" --no-tablespaces\n')
        wrapper.chmod(0o700)
        result = self.run_script()
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertIn("CREATE TABLE fixture", gzip.decompress((self.remote / "object").read_bytes()).decode())
        args = json.loads((self.remote / "dump_args").read_text())
        self.assertEqual(args[-3:], ["fixture_db", "logs", "--no-tablespaces"])
        self.assertEqual(list(self.work.iterdir()), [])

    def test_database_name_cannot_be_a_command_option(self):
        for name in ("--all-databases", "-a"):
            with self.subTest(name=name):
                self.write_env(extra="DB_DATABASE=" + name + "\n")
                result = self.run_script()
                self.assertNotEqual(result.returncode, 0)
                self.assertIn("DB_DATABASE may not begin with a hyphen", result.stderr)
                self.assertFalse(self.work.exists())
                self.assertFalse((self.remote / "dump_args").exists())
                self.assertFalse((self.remote / "calls").exists())

    def test_database_name_with_an_internal_hyphen_is_allowed(self):
        self.write_env(extra="DB_DATABASE=fixture-db\n")
        self.env["FAKE_DB_NAME"] = "fixture-db"
        result = self.run_script()
        self.assertEqual(result.returncode, 0, result.stderr)

    def test_logs_schema_is_kept_without_rows_and_other_data_is_preserved(self):
        result = self.run_script()
        self.assertEqual(result.returncode, 0, result.stderr)
        sql = gzip.decompress((self.remote / "object").read_bytes()).decode()
        self.assertIn('CREATE TABLE `logs`', sql)
        self.assertIn('CREATE TRIGGER logs_fixture', sql)
        self.assertNotIn('INSERT INTO `logs`', sql)
        self.assertNotIn('large-log-payload', sql)
        self.assertIn('INSERT INTO fixture VALUES (1)', sql)
        self.assertEqual(sql.count('CREATE PROCEDURE fixture_routine'), 1)
        self.assertEqual(sql.count('CREATE EVENT fixture_event'), 1)
        calls = [json.loads(line) for line in (self.remote / "dump_calls").read_text().splitlines()]
        self.assertEqual(len(calls), 2)
        self.assertIn('--ignore-table=fixture_db.logs', calls[0])
        self.assertNotIn('--no-data', calls[0])
        self.assertIn('--no-data', calls[1])
        self.assertNotIn('--ignore-table=fixture_db.logs', calls[1])

    def test_logs_schema_dump_failure_prevents_upload_and_keeps_archive(self):
        archive = self.assert_preserved(self.run_script(fail="schema"), ".sql.gz")
        self.assertFalse((self.remote / "calls").exists())
        self.assertIn('CREATE TABLE fixture', gzip.decompress(archive.read_bytes()).decode())
        self.assertEqual(len((self.remote / "dump_calls").read_text().splitlines()), 2)


if __name__ == "__main__":
    unittest.main(verbosity=2)
