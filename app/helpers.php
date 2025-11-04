<?php

use Illuminate\Support\HtmlString;

if (! function_exists('csrf_token')) {
    /**
     * Get the current CSRF token from the session store.
     */
    function csrf_token(): string
    {
        if (app()->bound('session')) {
            return app('session')->token();
        }

        throw new \RuntimeException('Session store not set on request.');
    }
}

if (! function_exists('csrf_field')) {
    /**
     * Build the hidden input field used by the @csrf Blade directive.
     */
    function csrf_field(): HtmlString
    {
        return new HtmlString('<input type="hidden" name="_token" value="'.csrf_token().'" autocomplete="off">');
    }
}
