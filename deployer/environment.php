<?php

declare(strict_types=1);

namespace Deployer;

set('current_timestamp', date('YmdHis'));
set('local_public_dir', str_replace('deployer', 'public', __DIR__));
