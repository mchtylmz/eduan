<?php

namespace App\Livewire\Languages;

use App\Actions\Languages\CreateOrUpdateLanguageAction;
use App\Enums\StatusEnum;
use App\Models\Language;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Livewire\Attributes\Lazy;
use Livewire\Component;

#[Lazy(isolate: true)]
class LanguageForm extends Component
{
    public Language $language;

    public string $code;
    public string $name = '';
    public StatusEnum $status = StatusEnum::ACTIVE;

    public string $permission = 'languages:add';

    public function mount(int $languageId = 0): void
    {
        if ($languageId && $this->language = Language::find($languageId)) {
            $this->code = $this->language->code;
            $this->name = $this->language->name;
            $this->status = $this->language->status;

            $this->permission = 'languages:update';
        }
    }

    public function rules(): array
    {
        return [
            'code' => [
                'required', 'string', Rule::unique('languages', 'code')->ignore($this->language->id ?? '-')
            ],
            'name' => 'required|string',
            'status' => [
                'required',
                new Enum(StatusEnum::class)
            ]
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'code' => __('Kodu'),
            'name' => __('Adı'),
            'status' => __('Durum'),
        ];
    }

    public function save()
    {
        $this->validate();

        CreateOrUpdateLanguageAction::run(
            data: [
                'code' => $this->code,
                'name' => $this->name,
                'status' => $this->status
            ],
            language: !empty($this->language) && $this->language->exists ? $this->language : null
        );

        return redirect()->route('admin.languages.index')->with([
            'status' => 'success',
            'message' => __('Dil kayıt edildi!')
        ]);
    }

    public function render()
    {
        return view('livewire.backend.languages.language-form');
    }
}
