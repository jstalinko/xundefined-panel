const path = require('path');
const fs = require('fs');
const dotenv = require('dotenv');

// 1. First read parent Laravel .env (../.env) as specifically requested
const parentEnvPath = path.resolve(__dirname, '../../.env');
let parentEnv = {};
if (fs.existsSync(parentEnvPath)) {
  const envConfig = dotenv.parse(fs.readFileSync(parentEnvPath));
  parentEnv = envConfig;
  for (const k in envConfig) {
    if (!process.env[k]) {
      process.env[k] = envConfig[k];
    }
  }
}

// 2. Also read local .env if available
const localEnvPath = path.resolve(__dirname, '../.env');
if (fs.existsSync(localEnvPath)) {
  const localConfig = dotenv.parse(fs.readFileSync(localEnvPath));
  for (const k in localConfig) {
    process.env[k] = localConfig[k];
  }
}

// Resolve APP_URL from ../.env (or process.env)
const rawAppUrl = process.env.APP_URL || parentEnv.APP_URL || 'http://127.0.0.1:8000';
const APP_URL = rawAppUrl.replace(/\/+$/, '');

// Resolve BOT_TOKEN
const BOT_TOKEN = process.env.BOT_TOKEN || process.env.TELEGRAM_BOT_TOKEN || parentEnv.TELEGRAM_BOT_TOKEN || '';

module.exports = {
  parentEnvPath,
  localEnvPath,
  APP_URL,
  API_BASE_URL: `${APP_URL}/api/telegram`,
  BOT_TOKEN,
  SUPPORT_USERNAME: process.env.SUPPORT_USERNAME || '@XundefinedSupport',
};
