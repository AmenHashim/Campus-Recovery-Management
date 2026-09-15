@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-muted/40 focus:border-primary focus:ring-primary rounded-md shadow-sm']) }}>
