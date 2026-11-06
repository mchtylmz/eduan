#!/usr/bin/env bash
# Run as a child process: bash /absolute/path/backup-to-r2.sh ... (never source).
if [[ "${BASH_SOURCE[0]}" != "$0" ]]; then
    printf 'Run with bash; do not source this script.\n' >&2
    return 1
fi
set +x
set -euo pipefail
umask 077

usage() {
    cat <<'HELP'
Usage: bash backup-to-r2.sh db|project --project-dir /absolute/laravel/root [--check]

--check validates local configuration and tools only; no files, dump or R2 requests.
Configuration comes from PROJECT/.env. No Laravel boot, Composer or Artisan.
APP_PUBLIC_PATH selects the public directory relative to PROJECT (default: public).
Required: BACKUP_WORK_DIR (outside project; parent must exist), BACKUP_R2_BUCKET,
BACKUP_R2_PREFIX, AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY, AWS_ENDPOINT.
DB mode additionally requires DB_CONNECTION=mysql, DB_HOST, DB_PORT, DB_DATABASE,
DB_USERNAME and DB_PASSWORD (may be empty). Use quoted, single-line dotenv values.
The logs table is included as schema only; its rows are excluded from DB backups.
HELP
}
fail() { printf 'ERROR: %s\n' "$1" >&2; exit 1; }
mode=''; project_dir=''; check_only=0
while (($#)); do
    case "$1" in
        db|project) [[ -z "$mode" ]] || fail 'Choose one backup mode.'; mode="$1"; shift ;;
        --project-dir) (($# >= 2)) || fail '--project-dir requires a path.'; project_dir="$2"; shift 2 ;;
        --check) check_only=1; shift ;;
        --help|-h) usage; exit 0 ;;
        *) fail 'Unknown argument. Use --help.' ;;
    esac
done
[[ "$mode" == db || "$mode" == project ]] || fail 'Select db or project mode.'
[[ "$project_dir" == /* && -d "$project_dir" ]] || fail 'Project directory must be an existing absolute path.'
project_dir=$(cd -P -- "$project_dir" && pwd)
[[ -f "$project_dir/artisan" && -r "$project_dir/.env" ]] || fail 'Expected a Laravel root with artisan and readable .env.'

trim() {
    local value="$1"
    value="${value#"${value%%[![:space:]]*}"}"
    value="${value%"${value##*[![:space:]]}"}"
    printf '%s' "$value"
}
# Allowlisted dotenv parser: never eval/source .env or execute substitutions.
# Quoted passwords support #, $, backticks and spaces literally. Interpolation is
# rejected outside single quotes for selected values; unrelated Laravel variables are not interpreted.
load_env() {
    local line key value tail quote rest decoded char next index
    while IFS= read -r line || [[ -n "$line" ]]; do
        line="${line%$'\r'}"
        [[ "$line" =~ ^[[:space:]]*(export[[:space:]]+)?([A-Za-z_][A-Za-z0-9_]*)[[:space:]]*=(.*)$ ]] || continue
        key="${BASH_REMATCH[2]}"; value="${BASH_REMATCH[3]}"
        case "$key" in
            APP_PUBLIC_PATH|DB_CONNECTION|DB_HOST|DB_PORT|DB_DATABASE|DB_USERNAME|DB_PASSWORD|DB_URL|DB_SOCKET|AWS_ACCESS_KEY_ID|AWS_SECRET_ACCESS_KEY|AWS_SESSION_TOKEN|AWS_ENDPOINT|BACKUP_WORK_DIR|BACKUP_R2_BUCKET|BACKUP_R2_PREFIX) ;;
            *) continue ;;
        esac
        value=$(trim "$value"); quote=''
        if [[ "${value:0:1}" == "'" || "${value:0:1}" == '"' ]]; then
            quote="${value:0:1}"; rest="${value:1}"; decoded=''; index=0
            while ((index < ${#rest})); do
                char="${rest:index:1}"
                if [[ "$char" == "$quote" ]]; then break; fi
                if [[ "$quote" == '"' && "$char" == '\' ]]; then
                    ((index+=1)); ((index < ${#rest})) || fail "Invalid quoted dotenv value for $key."
                    next="${rest:index:1}"
                    case "$next" in
                        '"'|'\'|'$') char="$next" ;;
                        n|r|t) fail "Control-character dotenv escape is unsupported for $key." ;;
                        *) fail "Unsupported dotenv escape for $key." ;;
                    esac
                fi
                decoded+="$char"; ((index+=1))
            done
            ((index < ${#rest})) || fail "Unclosed dotenv quote for $key."
            tail=$(trim "${rest:index+1}")
            [[ -z "$tail" || "${tail:0:1}" == '#' ]] || fail "Invalid trailing dotenv text for $key."
            value="$decoded"
        else
            value="${value%%#*}"; value=$(trim "$value")
            [[ "$value" != null && "$value" != \(null\) ]] || value=''
            [[ "$value" != *[[:space:]]* ]] || fail "Quote whitespace in dotenv value for $key."
        fi
        [[ "$quote" == "'" || "$value" != *'${'* ]] || fail "Use a literal dotenv value, without interpolation, for $key."
        [[ "$value" != *$'\n'* && "$value" != *$'\r'* ]] || fail "Invalid newline in dotenv value for $key."
        printf -v "$key" '%s' "$value"
    done < "$project_dir/.env"
}
# Explicit values in .env are required; inherited shell credentials/config do not
# silently select another database, project or bucket.
unset DB_CONNECTION DB_HOST DB_PORT DB_DATABASE DB_USERNAME DB_PASSWORD DB_URL DB_SOCKET
unset AWS_ACCESS_KEY_ID AWS_SECRET_ACCESS_KEY AWS_SESSION_TOKEN AWS_ENDPOINT
unset BACKUP_WORK_DIR BACKUP_R2_BUCKET BACKUP_R2_PREFIX
unset APP_PUBLIC_PATH
load_env
[[ -d "$project_dir/${APP_PUBLIC_PATH:-public}" ]] || fail 'Expected the public directory configured by APP_PUBLIC_PATH (default: public).'
for required in BACKUP_WORK_DIR BACKUP_R2_BUCKET BACKUP_R2_PREFIX AWS_ACCESS_KEY_ID AWS_SECRET_ACCESS_KEY AWS_ENDPOINT; do
    [[ -n "${!required:-}" ]] || fail "Missing $required in project .env."
done
[[ "$AWS_ENDPOINT" =~ ^https://[A-Za-z0-9.-]+\.r2\.cloudflarestorage\.com/?$ ]] || fail 'AWS_ENDPOINT must be an HTTPS Cloudflare R2 S3 endpoint.'
[[ "$BACKUP_R2_BUCKET" =~ ^[a-z0-9][a-z0-9.-]{1,61}[a-z0-9]$ ]] || fail 'Invalid backup bucket name.'
[[ "$BACKUP_R2_PREFIX" =~ ^[A-Za-z0-9][A-Za-z0-9/_-]*$ && "$BACKUP_R2_PREFIX" != */ ]] || fail 'Invalid backup prefix; use letters, numbers, slash, underscore or hyphen.'
[[ "$BACKUP_WORK_DIR" == /* && "$BACKUP_WORK_DIR" != / ]] || fail 'BACKUP_WORK_DIR must be an absolute non-root directory.'
BACKUP_WORK_DIR="${BACKUP_WORK_DIR%/}"
work_leaf=$(basename -- "$BACKUP_WORK_DIR")
[[ "$work_leaf" != . && "$work_leaf" != .. ]] || fail 'Invalid work directory.'
work_parent=$(cd -P -- "$(dirname -- "$BACKUP_WORK_DIR")" && pwd) || fail 'Work directory parent must exist.'
work_dir="$work_parent/$work_leaf"
[[ "$work_dir" != "$project_dir" && "$work_dir" != "$project_dir/"* ]] || fail 'Work directory must be outside the backed-up project.'
[[ ! -L "$work_dir" ]] || fail 'Work directory may not be a symlink.'
if [[ -e "$work_dir" ]]; then
    [[ -d "$work_dir" && -O "$work_dir" && -w "$work_dir" ]] || fail 'Work directory must be owned and writable by the current user.'
    permissions=$(LC_ALL=C ls -ld -- "$work_dir"); permissions="${permissions:0:10}"
    [[ "$permissions" == drwx------ ]] || fail 'Existing work directory must have private owner-only permissions (700).'
else
    [[ -w "$work_parent" ]] || fail 'Work directory parent is not writable.'
fi
for tool in aws mktemp openssl wc tr date mkdir rm rmdir; do
    command -v "$tool" >/dev/null 2>&1 || fail "Required tool is unavailable: $tool."
done
aws_version=$(aws --version 2>&1) || fail 'Cannot read AWS CLI version.'
[[ "$aws_version" == aws-cli/2.* ]] || fail 'AWS CLI v2 is required.'
if [[ "$mode" == db ]]; then
    [[ "${DB_CONNECTION:-}" == mysql ]] || fail 'DB mode supports Laravel DB_CONNECTION=mysql only.'
    for required in DB_HOST DB_PORT DB_DATABASE DB_USERNAME; do
        [[ -n "${!required:-}" ]] || fail "Missing $required in project .env."
    done
    [[ "$DB_DATABASE" != -* ]] || fail 'DB_DATABASE may not begin with a hyphen.'
    [[ ${DB_PASSWORD+x} ]] || fail 'DB_PASSWORD must be explicitly present (may be empty).'
    [[ "$DB_PORT" =~ ^[0-9]+$ ]] && ((10#$DB_PORT >= 1 && 10#$DB_PORT <= 65535)) || fail 'Invalid DB_PORT.'
    [[ -z "${DB_URL:-}" && -z "${DB_SOCKET:-}" ]] || fail 'DB_URL and DB_SOCKET overrides are unsupported; use explicit DB_HOST/DB_PORT settings.'
    for tool in mysqldump gzip; do
        command -v "$tool" >/dev/null 2>&1 || fail "Required tool is unavailable: $tool."
    done
else
    for tool in zip unzip; do
        command -v "$tool" >/dev/null 2>&1 || fail "Required tool is unavailable: $tool."
    done
fi
if ((check_only)); then
    printf 'Local check OK: %s mode. No backup or network request performed.\n' "$mode"
    exit 0
fi

# Private run directory; only our own temporary files are ever removed.
[[ -d "$work_dir" ]] || mkdir -- "$work_dir"
lock_dir="$work_dir/.backup.lock"
mkdir -- "$lock_dir" 2>/dev/null || fail 'Another job or stale lock exists. Check the job before removing its lock.'
run_dir=''; artifact=''; verified=0
cleanup() {
    local status=$?
    trap - EXIT
    if [[ -n "$run_dir" ]]; then
        rm -f -- "$run_dir/mysql.cnf"
        if ((status != 0)); then
            printf 'Backup failed; generated files were kept in %s\n' "$run_dir" >&2
        elif ((verified)); then
            rm -f -- "$run_dir/errors.log"
            rmdir -- "$run_dir" 2>/dev/null || true
        fi
    fi
    rm -f -- "$lock_dir/pid"
    rmdir -- "$lock_dir" 2>/dev/null || true
    exit "$status"
}
trap cleanup EXIT
trap 'exit 130' INT
trap 'exit 143' TERM
printf '%s\n' "$$" > "$lock_dir/pid"
run_dir=$(mktemp -d "$work_dir/run.XXXXXXXXXX")
run_id="${run_dir##*/}"
stamp=$(date -u '+%Y%m%dT%H%M%SZ')
log_file="$run_dir/errors.log"
if [[ "$mode" == db ]]; then
    artifact="$run_dir/$stamp-$run_id.sql.gz"
    # MySQL option-file escaping; password is absent from arguments and stdout.
    mysql_option() {
        local value="$2"
        value="${value//\\/\\\\}"; value="${value//\"/\\\"}"
        printf '%s="%s"\n' "$1" "$value"
    }
    {
        printf '[client]\n'
        mysql_option host "$DB_HOST"; mysql_option port "$DB_PORT"
        mysql_option user "$DB_USERNAME"; mysql_option password "$DB_PASSWORD"
    } > "$run_dir/mysql.cnf"
    # InnoDB consistent snapshot; do not lock/write/import/reset the database.
    # Include stored routines/events/triggers; stop on permission/tool errors.
    # No "--" terminator: hosting wrappers may append their own options. The
    # database-name guard above prevents this operand from becoming an option.
    unset MYSQL_PWD
    dump_args=(--defaults-file="$run_dir/mysql.cnf" --single-transaction --quick
        --skip-lock-tables --no-tablespaces --routines --events --triggers
        --set-gtid-purged=OFF)
    # MySQL 8.4-compatible data exclusion: keep all other tables in the
    # first snapshot, then append logs definitions without its rows or duplicate
    # routines/events. Either dump failing stops the stream before upload.
    {
        MYSQL_TEST_LOGIN_FILE="$run_dir/unused-login.cnf" mysqldump "${dump_args[@]}" \
            --ignore-table="$DB_DATABASE.logs" "$DB_DATABASE" || exit "$?"
        MYSQL_TEST_LOGIN_FILE="$run_dir/unused-login.cnf" mysqldump "${dump_args[@]}" \
            --no-data --skip-routines --skip-events "$DB_DATABASE" logs || exit "$?"
    } 2>"$log_file" | gzip -c > "$artifact"
    gzip -t -- "$artifact" 2>>"$log_file" || fail 'Generated SQL.gz is invalid.'
    uncompressed_size=$(gzip -cd -- "$artifact" | wc -c | tr -d '[:space:]')
    [[ "$uncompressed_size" != 0 ]] || fail 'Generated SQL dump is empty.'
    object_key="$BACKUP_R2_PREFIX/db/${artifact##*/}"
else
    artifact="$run_dir/$stamp-$run_id.zip"
    # Include dotfiles, .env, uploads, dependencies and .git. Store symlinks as
    # links, without traversing files outside this project. Work dir is outside.
    (cd -- "$project_dir" && ZIPOPT='' zip -q -r -y "$artifact" .) 2>"$log_file" || fail 'Project ZIP creation failed.'
    zip -T "$artifact" >>"$log_file" 2>&1 || fail 'Generated project ZIP is invalid.'
    object_key="$BACKUP_R2_PREFIX/project/${artifact##*/}"
fi
[[ -s "$artifact" ]] || fail 'Generated backup is empty.'
size=$(wc -c < "$artifact" | tr -d '[:space:]')
digest=$(openssl dgst -sha256 "$artifact"); digest="${digest##* }"
[[ "$digest" =~ ^[0-9a-f]{64}$ ]] || fail 'Cannot calculate backup SHA-256.'

export AWS_ACCESS_KEY_ID AWS_SECRET_ACCESS_KEY
[[ -z "${AWS_SESSION_TOKEN:-}" ]] || export AWS_SESSION_TOKEN
unset AWS_PROFILE AWS_DEFAULT_PROFILE
export AWS_EC2_METADATA_DISABLED=true AWS_PAGER=''
# R2 does not implement every optional S3 checksum header. Independently verify
# the complete stored bytes below instead of relying on a multipart ETag.
export AWS_REQUEST_CHECKSUM_CALCULATION=when_required
export AWS_RESPONSE_CHECKSUM_VALIDATION=when_required
aws_args=(--endpoint-url "$AWS_ENDPOINT" --region auto --no-cli-pager)
remote="s3://$BACKUP_R2_BUCKET/$object_key"
printf 'Uploading %s backup (%s bytes).\n' "$mode" "$size"
aws "${aws_args[@]}" s3 cp "$artifact" "$remote" --only-show-errors --metadata "sha256=$digest" 2>>"$log_file" || fail 'R2 upload failed.'
head=$(aws "${aws_args[@]}" s3api head-object --bucket "$BACKUP_R2_BUCKET" --key "$object_key" \
    --query '[ContentLength,Metadata.sha256]' --output text 2>>"$log_file") || fail 'R2 object check failed.'
IFS=$'\t' read -r remote_size remote_digest <<< "$head"
[[ "$remote_size" == "$size" && "$remote_digest" == "$digest" ]] || fail 'R2 size/metadata verification failed.'
# Stream the uploaded object back through SHA-256: no second large local copy.
readback=$(aws "${aws_args[@]}" s3 cp "$remote" - --only-show-errors 2>>"$log_file" | openssl dgst -sha256) || fail 'R2 readback failed.'
[[ "${readback##* }" == "$digest" ]] || fail 'R2 stored-content SHA-256 mismatch.'
verified=1
rm -f -- "$artifact"
printf 'Backup verified and local archive removed: %s\n' "$object_key"
