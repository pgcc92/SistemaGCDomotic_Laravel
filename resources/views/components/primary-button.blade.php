<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center rounded-lg border border-transparent bg-primary px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-primary/35 disabled:opacity-60 dark:bg-primary dark:text-white dark:hover:bg-primary/90']) }}>
    {{ $slot }}
</button>
