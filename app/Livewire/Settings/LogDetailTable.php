<?php

namespace App\Livewire\Settings;

use App\Helpers\LogDataMasker;
use App\Models\Log;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;
use Livewire\Component;

class LogDetailTable extends Component
{
    #[Locked]
    public Log $log;

    #[Locked]
    public array $currentData = [];

    #[Locked]
    public array $changedFields = [];

    public function mount(int $logId): void
    {
        $this->log = Log::find($logId);

        $current = $this->rawCurrentData();
        $old = json_decode($this->log->data, true);
        $compare = fn ($value) => is_array($value) ? json_encode($value) : (string) $value;
        $this->changedFields = array_keys(array_diff_assoc(array_map($compare, $old), array_map($compare, $current)));
        $this->currentData = LogDataMasker::mask($current);
    }

    public function jsonData(): array
    {
        return LogDataMasker::mask(json_decode($this->log->data, true));
    }

    public function currentData(): array
    {
        return LogDataMasker::mask($this->rawCurrentData());
    }

    public function hydrate(): void
    {
        $this->currentData = LogDataMasker::mask($this->currentData);
    }

    protected function rawCurrentData(): array
    {
        if (! in_array($this->log->log_type, ['create', 'delete']) && $this->log->table_name) {
            $log = Log::orderBy('id', 'ASC')
                ->where('id', '>', $this->log->id)
                ->where('table_name', $this->log->table_name)
                ->where('data_id', $this->log->data_id)
                ->first();

            if ($log) {
                return json_decode($log->data, true);
            } else {
                return (array) DB::table($this->log->table_name)->find($this->log->data_id);
            }
        }

        return [];
    }

    public function diff(string $key): bool
    {
        return in_array($key, $this->changedFields, true);
    }

    public function render()
    {
        return view('livewire.backend.settings.log-detail-table');
    }
}
