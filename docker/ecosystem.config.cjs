module.exports = {
    apps: [
        {
            name: 'nuvelo-ssr',
            script: 'bootstrap/ssr/ssr.js',
            watch: false,
            instances: 1,
            exec_mode: 'fork',
            env: {
                NODE_ENV: 'production',
            },
        },
    ],
};
