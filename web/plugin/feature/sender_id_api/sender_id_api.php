<?php

    defined('_SECURE_') or die('Forbidden');

    if (!auth_isadmin()) {
        auth_block();
    }
