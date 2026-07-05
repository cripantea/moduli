@setup
$server     = 'srv961648.hstgr.cloud';
$user       = 'cristi';
$baseDir    = '/var/www/html/sinistri-tenant';
$repository = 'cripantea/sinistropro.git';
$branch     = $branch ?? 'main';
$php        = 'php';

$userAndServer  = "{$user}@{$server}";
$releasesDir    = "{$baseDir}/releases";
$persistentDir  = "{$baseDir}/persistent";
$currentDir     = "{$baseDir}/current";
$newReleaseName = date('Ymd-His');
$newReleaseDir  = "{$releasesDir}/{$newReleaseName}";

function logMessage($message) {
    return "echo '\033[32m" . $message . "\033[0m';\n";
}
@endsetup

@servers(['local' => '127.0.0.1', 'remote' => $userAndServer])

@story('deploy')
    cloneRepository
    runComposer
    runNpm
    generateAssets
    updateSymlinks
    optimizeInstallation
    migrateDatabase
    blessNewRelease
    cleanOldReleases
    finishDeploy
@endstory

{{-- ─── Clone ───────────────────────────────────────────────────── --}}
@task('cloneRepository', ['on' => 'remote'])
    {{ logMessage('🌀  Cloning repository…') }}

    [ -d {{ $releasesDir }} ]             || mkdir -p {{ $releasesDir }};
    [ -d {{ $persistentDir }} ]           || mkdir -p {{ $persistentDir }};
    [ -d {{ $persistentDir }}/storage ]   || mkdir -p {{ $persistentDir }}/storage;
    [ -d {{ $persistentDir }}/storage/app ]               || mkdir -p {{ $persistentDir }}/storage/app;
    [ -d {{ $persistentDir }}/storage/app/public ]        || mkdir -p {{ $persistentDir }}/storage/app/public;
    [ -d {{ $persistentDir }}/storage/framework ]         || mkdir -p {{ $persistentDir }}/storage/framework;
    [ -d {{ $persistentDir }}/storage/framework/cache ]   || mkdir -p {{ $persistentDir }}/storage/framework/cache;
    [ -d {{ $persistentDir }}/storage/framework/sessions ]|| mkdir -p {{ $persistentDir }}/storage/framework/sessions;
    [ -d {{ $persistentDir }}/storage/framework/views ]   || mkdir -p {{ $persistentDir }}/storage/framework/views;
    [ -d {{ $persistentDir }}/storage/logs ]              || mkdir -p {{ $persistentDir }}/storage/logs;

    mkdir {{ $newReleaseDir }};
    git clone --depth 1 --branch {{ $branch }} git@github.com:{{ $repository }} {{ $newReleaseDir }};

    mkdir -p {{ $newReleaseDir }}/bootstrap/cache;
    chmod -R 775 {{ $newReleaseDir }}/bootstrap/cache;

    echo "{{ $newReleaseName }}" > {{ $newReleaseDir }}/public/release-name.txt;
@endtask

{{-- ─── Composer ────────────────────────────────────────────────── --}}
@task('runComposer', ['on' => 'remote'])
    {{ logMessage('🚚  Running Composer…') }}
    cd {{ $newReleaseDir }};
    composer install --prefer-dist --no-scripts --no-dev -q -o;
@endtask

{{-- ─── NPM ─────────────────────────────────────────────────────── --}}
@task('runNpm', ['on' => 'remote'])
    {{ logMessage('📦  Running npm install…') }}
    cd {{ $newReleaseDir }};
    npm ci --prefer-offline;
@endtask

{{-- ─── Build assets ────────────────────────────────────────────── --}}
@task('generateAssets', ['on' => 'remote'])
    {{ logMessage('🌅  Building frontend assets…') }}
    cd {{ $newReleaseDir }};
    npm run build;
@endtask

{{-- ─── Symlinks ────────────────────────────────────────────────── --}}
@task('updateSymlinks', ['on' => 'remote'])
    {{ logMessage('🔗  Updating symlinks…') }}

    rm -rf {{ $newReleaseDir }}/storage;
    ln -nfs {{ $persistentDir }}/storage {{ $newReleaseDir }}/storage;

    ln -nfs {{ $baseDir }}/.env {{ $newReleaseDir }}/.env;
@endtask

{{-- ─── Optimize ────────────────────────────────────────────────── --}}
@task('optimizeInstallation', ['on' => 'remote'])
    {{ logMessage('✨  Optimizing…') }}
    cd {{ $newReleaseDir }};
    {{ $php }} artisan clear-compiled;
@endtask

{{-- ─── Migrate ─────────────────────────────────────────────────── --}}
@task('migrateDatabase', ['on' => 'remote'])
    {{ logMessage('🗄️  Running migrations…') }}
    cd {{ $newReleaseDir }};
    {{ $php }} artisan migrate --force;
@endtask

{{-- ─── Bless ───────────────────────────────────────────────────── --}}
@task('blessNewRelease', ['on' => 'remote'])
    {{ logMessage('🙏  Blessing new release…') }}

    ln -nfs {{ $newReleaseDir }} {{ $currentDir }};

    cd {{ $newReleaseDir }};
    {{ $php }} artisan config:clear;
    {{ $php }} artisan view:clear;
    {{ $php }} artisan route:clear;
    {{ $php }} artisan schedule:clear-cache;
    {{ $php }} artisan config:cache;
    {{ $php }} artisan route:cache;
    {{ $php }} artisan view:cache;
    {{ $php }} artisan storage:link;
    {{ $php }} artisan queue:restart;
@endtask

{{-- ─── Clean old releases ──────────────────────────────────────── --}}
@task('cleanOldReleases', ['on' => 'remote'])
    {{ logMessage('🚾  Cleaning up old releases (keep 5)…') }}
    ls -dt {{ $releasesDir }}/* | tail -n +6 | xargs -d "\n" rm -rf;
@endtask

{{-- ─── Done ────────────────────────────────────────────────────── --}}
@task('finishDeploy', ['on' => 'local'])
    {{ logMessage('🚀  sinistropro deployed successfully!') }}
@endtask

{{-- ══════════════════════════════════════════════════════════════
     HOTFIX — deploy solo codice senza build completa
     Uso: vendor/bin/envoy run hotfix
     ══════════════════════════════════════════════════════════════ --}}
@story('hotfix')
    hotfixPull
    hotfixOptimize
@endstory

@task('hotfixPull', ['on' => 'remote'])
    {{ logMessage('⚡  Hotfix — pulling latest code…') }}
    cd {{ $currentDir }};
    git pull origin {{ $branch }};
@endtask

@task('hotfixOptimize', ['on' => 'remote'])
    {{ logMessage('⚡  Hotfix — clearing caches…') }}
    cd {{ $currentDir }};
    {{ $php }} artisan config:clear;
    {{ $php }} artisan view:clear;
    {{ $php }} artisan route:clear;
    {{ $php }} artisan config:cache;
    {{ $php }} artisan route:cache;
    {{ $php }} artisan queue:restart;
@endtask
