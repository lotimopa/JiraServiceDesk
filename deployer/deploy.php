<?php

namespace Deployer;

require 'recipe/symfony.php';
require 'environment.php';

//
// APPLICATION
//
set('application', 'jira-service-desk');

//
// REPOSITORY
//
set('repository', 'git@gitlab.choosit.com:choosik/symfony/jira-service-desk.git');

set('shared_files', [
    '.env.local',
    '.env.local.php',
]);
set('shared_dirs', [
    'var/log',
    'var/sessions',
]);
set('clear_paths', [
    'deployer',
]);
set('writable_dirs', [
    'var',
]);
set('bin/php', 'php8.4');
set('release_name', static fn () => date('YmdHis'));
set('keep_releases', 3);
set('remote_user', 'jsd-deploy');
set('deploy_path', '~/html');

//
// HOSTS
//
import('hosts.yml');

//
// TASKS
//
task('deploy:frontend', static function () {
    writeln('✨ Uploading Vite build');
    upload('{{local_public_dir}}/build/', '{{release_path}}/public/build');
});

task('deploy:dump-env', static function () {
    writeln('📄 Dumping environment');
    run('cd {{release_path}} && {{bin/composer}} dump-env {{symfony_env}}');
});

task('deploy:messenger', static function () {
    if (has('messenger_rerun') && get('messenger_rerun')) {
        writeln('📦️ Messenger consumer restart');
        if (has('previous_release')) {
            run('{{bin/php}} {{previous_release}}/bin/console messenger:stop-workers', no_throw: true);
        }
        run('cd {{release_path}} && {{bin/php}} bin/console messenger:setup-transports');
    }
});

task('database:migrate', static function () {
    if (has('database_migration') && get('database_migration')) {
        writeln('🗃️  Database migration');
        run('cd {{release_path}} && {{bin/php}} bin/console doctrine:migrations:migrate --no-interaction');
    }
});

task('info', static function () {
    $labels = get('labels');

    if (isset($labels['env'])) {
        writeln('Environment : '.$labels['env']);
    }
});

//
// HOOKS
//
after('deploy:setup', 'info');
after('deploy:vendors', 'database:migrate');
after('deploy:vendors', 'deploy:frontend');
after('deploy:cache:clear', 'deploy:dump-env');
after('deploy:dump-env', 'deploy:messenger');

after('deploy:failed', 'deploy:unlock');
