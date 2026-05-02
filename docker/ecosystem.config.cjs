const fs = require('fs');

const apps = [
    {
        name: 'nuvelo-queue',
        script: 'php',
        args: 'artisan queue:work --sleep=3 --tries=3 --timeout=120',
        watch: false,
        instances: 1,
        exec_mode: 'fork',
        autorestart: true,
    },
    {
        name: 'nuvelo-reverb',
        script: 'php',
        args: `artisan reverb:start --host=0.0.0.0 --port=${process.env.REVERB_SERVER_PORT || 8082}`,
        watch: false,
        instances: 1,
        exec_mode: 'fork',
        autorestart: true,
    },
];

if (fs.existsSync('bootstrap/ssr/ssr.js')) {
    apps.unshift({
        name: 'nuvelo-ssr',
        script: 'bootstrap/ssr/ssr.js',
        watch: false,
        instances: 1,
        exec_mode: 'fork',
        env: {
            NODE_ENV: 'production',
        },
    });
}

module.exports = { apps };
