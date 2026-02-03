# noerd/modal

A modal system for Livewire 4. Can open every livewire component in a modal. 

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

Add Modal 
```bash
<body>
<livewire:noerd-modal/> <!-- must be loaded before livewire components -->
...
</head>
```

## Usage

Open a Livewire Component in a Modal from a button for example
```html
<button type="button"
    @click="$modal('livewire-component-name', { optionalParameter: 'value1' })">
    Open Modal
</button>
```


