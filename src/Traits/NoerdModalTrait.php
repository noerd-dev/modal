<?php

namespace NoerdModal\Traits;

use Illuminate\Support\Str;
use Livewire\Attributes\Url;

trait NoerdModalTrait
{
    public bool $showSuccessIndicator = false;

    #[Url(as: 'tab', keep: false, except: 1)]
    public int $currentTab = 1;

    public array $pageLayout;

    public bool $disableModal = false;

    public array $relationTitles = [];

    public mixed $context = '';

    protected function componentName(): string
    {
        return defined('static::COMPONENT') ? static::COMPONENT : $this->getName();
    }

    /**
     * Process mount for modal detail components.
     * Returns false if the model doesn't exist (null, deleted, or inaccessible).
     * Automatically sets the model data array (e.g., customerData for customer-detail).
     *
     * @param  string  $component  The component name for loading page layout
     * @param  mixed  $model  The model instance or array
     * @param  array|null  $pageLayout  Optional pre-loaded page layout; if null, only processes model
     * @return bool True if model exists and can be displayed, false otherwise
     */
    public function mountModalProcess(string $component, $model, ?array $pageLayout = null): bool
    {
        if ($pageLayout !== null) {
            $this->pageLayout = $pageLayout;
        }

        // Handle null/non-existent/deleted models gracefully
        $modelExists = $model !== null && (is_array($model) ? ! empty($model['id']) : $model->exists);

        if (! $modelExists) {
            $this->{self::ID} = null;
            $this->dispatch('closeModal', componentName: $component, source: null, modalKey: null);

            return false;
        }

        $this->{self::ID} = is_array($model) ? $model['id'] : $model->id;

        // Automatically set modelData property (e.g., customer-detail -> customerData)
        $dataProperty = Str::camel(Str::before($component, '-detail')) . 'Data';
        if (property_exists($this, $dataProperty)) {
            $this->{$dataProperty} = is_array($model) ? $model : $model->toArray();
        }

        return true;
    }

    /**
     * Handle select action - dispatch selection event and close modal.
     */
    public function selectAction(mixed $modelId = null, mixed $relationId = null): void
    {
        $this->dispatch($this->getSelectEvent(), $modelId, $this->context);

        $this->dispatch('closeTopModal');
    }

    public function closeModalProcess(?string $source = null, ?string $modalKey = null): void
    {
        if (defined('self::ID')) {
            $this->{self::ID} = '';
        }
        $this->currentTab = 1;
        $this->dispatch('closeModal', componentName: $this->componentName(), source: $source, modalKey: $modalKey);

        if ($source) {
            $this->dispatch('refreshList-' . $source);
        }
    }

    public function storeProcess($model): void
    {
        $this->showSuccessIndicator = true;
        $this->{self::ID} = $model['id'];
    }

    /**
     * Validate using rules from pageLayout YAML configuration.
     * Fields with 'required: true' will be validated as required.
     */
    public function validateFromLayout(): void
    {
        $rules = [];
        $this->extractRulesFromFields($this->pageLayout['fields'] ?? [], $rules);

        if (! empty($rules)) {
            $this->validate($rules);
        }
    }

    /**
     * Get the event name for select mode.
     * Derives from COMPONENT: 'customers-list' -> 'customerSelected'
     */
    protected function getSelectEvent(): string
    {
        $entity = Str::singular(Str::before($this->componentName(), '-list'));

        return Str::camel($entity) . 'Selected';
    }

    /**
     * Recursively extract validation rules from fields array.
     */
    protected function extractRulesFromFields(array $fields, array &$rules): void
    {
        foreach ($fields as $field) {
            if (($field['type'] ?? '') === 'block') {
                $this->extractRulesFromFields($field['fields'] ?? [], $rules);

                continue;
            }

            if (! isset($field['name'])) {
                continue;
            }

            $fieldRules = [];

            if ($field['required'] ?? false) {
                $fieldRules[] = 'required';
            }

            if (! empty($fieldRules)) {
                $rules[$field['name']] = $fieldRules;
            }
        }
    }
}
