<?php

if (! function_exists('tenant')) {
    function tenant()
    {
        return app()->has('tenant') ? app('tenant') : null;
    }
}




