# noerd/modal

**A modal system for Laravel Livewire 4.**<br/>
Open any Livewire component in a modal — no traits, no modifications to your livewire component code.

## Installation

```bash
composer require noerd/modal
```

## Configuration
Add Assets between your head tags.

```bash
<head>
...
<x-noerd::modal-assets/>
...
</head>
```

Add Modal Component to your layout
```html
<body x-data>
  <livewire:noerd-modal/> <!-- must be loaded before livewire components -->
...
</head>
```

## Example Usage

Opening a Livewire component in a modal via a button
```html
<button type="button"
    @click="$modal('livewire-component-name')">
    Open Modal
</button>
```

If you want to add parameters to your component which is opened in a modal: 
```html
<button type="button"
    @click="$modal('livewire-component-name', { name: 'John Doe' })">
    Open Modal
</button>
```

```php
<?php

use Livewire\Component;

new class extends Component
{
    public string $name = ''; // will be set to John Doe
};
?>

<div class="p-4">
    {{$name}} {{-- Will display John Doe --}}
</div>
```
