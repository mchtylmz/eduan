<?php

namespace App\Livewire\Settings;

use App\Models\Log;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class LogDetailTable extends Component
{
    public Log $log;

    public array $currentData = [];

    public function mount(int $logId): void
    {
        $this->log = Log::find($logId);

        $this->currentData = $this->currentData();
    }

    public function jsonData(): array
    {
        return json_decode($this->log->data, true);
    }

    public function currentData(): array
    {
        if (!in_array($this->log->log_type, ['create', 'delete']) && $this->log->table_name) {
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
        return array_key_exists($key, array_diff_assoc($this->jsonData(), $this->currentData));
    }

    public function render()
    {
        return view('livewire.backend.settings.log-detail-table');
    }
}
