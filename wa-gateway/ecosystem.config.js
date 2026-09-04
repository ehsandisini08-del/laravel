module.exports = {
  apps: [{
    name: 'wa-gateway',
    script: './src/index.js',
    instances: 1,
    exec_mode: 'fork',
    autorestart: true,
    watch: false,
    max_memory_restart: '500M',
    env: {
      NODE_ENV: 'production',
      PORT: 3001
    },
    error_file: './logs/error.log',
    out_file: './logs/out.log',
    log_date_format: 'YYYY-MM-DD HH:mm:ss Z',
    merge_logs: true,
    min_uptime: '10s',
    max_restarts: 50,
    restart_delay: 3000,
    kill_timeout: 5000,
    listen_timeout: 3000,
    shutdown_with_message: false
  }]
};
