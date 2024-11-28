<?php

namespace App\Traits;

use Rappasoft\LaravelLivewireTables\Views\Column;

trait LivewireTableConfigure
{
    public function configure(): void
    {
        $class = get_class($this);
        //$this->setDebugStatus(true);
        $this
            ->setPrimaryKey('id')
            ->setPerPageAccepted([$this->defaultPerPage ?? 15, 25, 25, 50, 100])
            ->setDefaultPerPage($this->defaultPerPage ?? 15)
            ->setSingleSortingEnabled()
            ->setUseHeaderAsFooterEnabled()
            ->setHideBulkActionsWhenEmptyEnabled()
            ->enableAllEvents()
            ->setLoadingPlaceholderEnabled()
            ->setLoadingPlaceholderContent(__('Yükleniyor') . '...')
            ->setFilterLayoutSlideDown()
            ->setEagerLoadAllRelationsStatus(false)
            ->setShouldRetrieveTotalItemCountDisabled()
            ->setQueryStringEnabled()
            ->setRememberColumnSelectionDisabled()
            ->setColumnSelectDisabled()
            ->useComputedPropertiesDisabled();

        // RoleTable
        if (str_contains($class, 'RoleTable')) {
            $this->setThAttributes(
                fn(Column $column) => $column->isField('id') ? ['class' => 'mw-100 w-50'] : []
            );
        }
    }
}
