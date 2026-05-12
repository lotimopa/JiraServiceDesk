<?php

namespace Deployer;

require 'recipe/symfony.php';

// Config

set('application', 'support2025');
set('repository', 'git@gitlab.choosit.com:choosik/tools/jira-service-desk.git');
set('bin/php', 'php8.4');
set('release_name', date('YmdHis'));
set('keep_releases', 3);

set('shared_files', [
    '.env.local',
    '.env.local.php',
]);
set('shared_dirs', [
    'var/log',
    'var/sessions',
]);
set('writable_dirs', [
    'var',
]);

// Hosts

import('hosts.yml');

// Tasks

task('deploy:dump-env', static function () {
    run('cd {{release_path}} && {{bin/composer}} dump-env {{symfony_env}}');
});

task('deploy:restart_messenger', static function () {
    if (has('previous_release')) {
        run('{{bin/php}} {{previous_release}}/bin/console messenger:stop-workers');
    }
    run('cd {{release_path}} && {{bin/php}} bin/console messenger:setup-transports');
});

task('deploy:frontend', static function () {
    upload(__DIR__ . '/../public/build/', '{{release_path}}/public/build/');
});

// Hooks

after('deploy:update_code', 'deploy:frontend');
after('deploy:vendors', 'database:migrate');
after('deploy:cache:clear', 'deploy:dump-env');
after('deploy:dump-env', 'deploy:restart_messenger');

after('deploy:failed', 'deploy:unlock');
