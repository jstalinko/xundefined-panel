const { Telegraf } = require('telegraf');
const { APP_URL, API_BASE_URL, BOT_TOKEN } = require('./config');
const { autoRegisterMiddleware } = require('./middleware/auth');
const { handleStart } = require('./handlers/start');
const {
  handleProducts,
  handleProductDetail,
  handleConfirmBuy,
  handleExecuteBuy,
} = require('./handlers/products');
const {
  handleDownload,
  handleDownloadVersions,
  handleSendDownloadFile,
} = require('./handlers/download');
const { handleOrders } = require('./handlers/orders');
const { handleActivities } = require('./handlers/activities');
const {
  handleDomains,
  handleAddDomainPrompt,
  handleAddDomainCommand,
  handleDeleteDomain,
} = require('./handlers/domains');
const { handleProfile } = require('./handlers/profile');
const {
  handleBalance,
  handleCryptoStart,
  handleCryptoSelectAmount,
  handleOtherPayment,
  handleCryptoGenerateInvoice,
  handleCheckTopupStatus,
  handleDemoTopup,
} = require('./handlers/balance');

console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
console.log('🚀 Initializing Xundefined Telegram Bot...');
console.log(`📡 Backend APP_URL: ${APP_URL}`);
console.log(`🔗 API Base Endpoint: ${API_BASE_URL}`);
console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

if (!BOT_TOKEN) {
  console.warn('⚠️  WARNING: BOT_TOKEN is not set!');
  console.warn('   Please set TELEGRAM_BOT_TOKEN in ../.env or BOT_TOKEN in .env');
  console.warn('   Example: TELEGRAM_BOT_TOKEN="123456789:ABCdefGhIJKlmNoPQRsTUVwxyZ"');
  console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
}

const bot = new Telegraf(BOT_TOKEN || 'dummy:token');

// Global middleware for auto-registration & user state loading
bot.use(autoRegisterMiddleware);

// --- Command Handlers ---
bot.command('start', handleStart);
bot.command('menu', handleStart);
bot.command('products', handleProducts);
bot.command('download', handleDownload);
bot.command('orders', handleOrders);
bot.command('activities', handleActivities);
bot.command('domains', handleDomains);
bot.command('adddomain', handleAddDomainCommand);
bot.command('profile', handleProfile);
bot.command('balance', handleBalance);

bot.command('help', async (ctx) => {
  const helpText = `📖 *Bot Commands & Menu Guide*
━━━━━━━━━━━━━━━━━━━━
• /start - Start bot and show account & balance
• /menu - Display interactive menu
• /products - Browse script websites
• /download - Access purchased script files
• /orders - View your purchase history
• /activities - View recent account activity
• /domains - Manage authorized script domains
• /adddomain <name> - Register script domain (e.g. \`/adddomain example.com\`)
• /profile - Check your profile & statistics
• /balance - Check balance & deposit guide
━━━━━━━━━━━━━━━━━━━━`;
  await ctx.reply(helpText, { parse_mode: 'Markdown' });
});

// --- Reply Keyboard Matches ---
// Products
bot.hears(['🛍️ Products', 'Products', 'products'], handleProducts);
// Download
bot.hears(['📥 Download', 'Download', 'download'], handleDownload);
// Orders
bot.hears(['📋 Orders', 'Orders', 'orders'], handleOrders);
// Activities
bot.hears(['📊 Activities', 'Activities', 'activities'], handleActivities);
// Domains
bot.hears(['🌐 Domains', 'Domains', 'domains'], handleDomains);
// Profile
bot.hears(['👤 Profile', 'Profile', 'profile'], handleProfile);
// Balance
bot.hears(['💳 Balance', 'Balance', 'balance'], handleBalance);

// --- Callback Query Handlers ---
bot.action('main_menu', async (ctx) => {
  await ctx.answerCbQuery();
  return handleStart(ctx);
});

bot.action('menu_products', async (ctx) => {
  await ctx.answerCbQuery();
  return handleProducts(ctx);
});

bot.action('menu_download', async (ctx) => {
  await ctx.answerCbQuery();
  return handleDownload(ctx);
});

bot.action('menu_orders', async (ctx) => {
  await ctx.answerCbQuery();
  return handleOrders(ctx);
});

bot.action('menu_activities', async (ctx) => {
  await ctx.answerCbQuery();
  return handleActivities(ctx);
});

bot.action('menu_domains', async (ctx) => {
  await ctx.answerCbQuery();
  return handleDomains(ctx);
});

bot.action('menu_profile', async (ctx) => {
  await ctx.answerCbQuery();
  return handleProfile(ctx);
});

bot.action('menu_balance', async (ctx) => {
  await ctx.answerCbQuery();
  return handleBalance(ctx);
});

// Product detail action: prod_view_<id>
bot.action(/^prod_view_(\d+)$/, async (ctx) => {
  await ctx.answerCbQuery();
  const productId = ctx.match[1];
  return handleProductDetail(ctx, productId);
});

// Product confirm buy action: prod_confirm_buy_<id>
bot.action(/^prod_confirm_buy_(\d+)$/, async (ctx) => {
  await ctx.answerCbQuery();
  const productId = ctx.match[1];
  return handleConfirmBuy(ctx, productId);
});

// Product execute buy action: prod_do_buy_<id>
bot.action(/^prod_do_buy_(\d+)$/, async (ctx) => {
  await ctx.answerCbQuery();
  const productId = ctx.match[1];
  return handleExecuteBuy(ctx, productId);
});

// Download actions: select product -> select version -> send file
bot.action(/^dl_prod_(\d+)$/, async (ctx) => {
  await ctx.answerCbQuery();
  const productId = ctx.match[1];
  return handleDownloadVersions(ctx, productId);
});

bot.action(/^dl_file_(\d+)_(.+)$/, async (ctx) => {
  try {
    await ctx.answerCbQuery();
  } catch (cbErr) {
    // Ignore cb query answer error
  }
  const productId = ctx.match[1];
  const version = ctx.match[2];
  return handleSendDownloadFile(ctx, productId, version);
});

// Domains actions
bot.action('dom_add_prompt', async (ctx) => {
  await ctx.answerCbQuery();
  return handleAddDomainPrompt(ctx);
});

bot.action(/^dom_del_(\d+)$/, async (ctx) => {
  const domainId = ctx.match[1];
  return handleDeleteDomain(ctx, domainId);
});

// Balance actions
bot.action('bal_crypto_start', async (ctx) => {
  await ctx.answerCbQuery();
  return handleCryptoStart(ctx);
});

bot.action(/^bal_amt_(\d+)$/, async (ctx) => {
  await ctx.answerCbQuery();
  const amount = ctx.match[1];
  return handleCryptoSelectAmount(ctx, amount);
});

bot.action('bal_other_payment', async (ctx) => {
  await ctx.answerCbQuery();
  return handleOtherPayment(ctx);
});

bot.action(/^bal_coin_(\d+)_([A-Za-z0-9\.]+)$/, async (ctx) => {
  const amount = ctx.match[1];
  const currency = ctx.match[2];
  return handleCryptoGenerateInvoice(ctx, amount, currency);
});

bot.action(/^bal_chk_([A-Za-z0-9\-]+)$/, async (ctx) => {
  const invoice = ctx.match[1];
  return handleCheckTopupStatus(ctx, invoice);
});

bot.action(/^bal_topup_demo_(\d+)$/, async (ctx) => {
  const amount = ctx.match[1];
  return handleDemoTopup(ctx, amount);
});

// Global error handler
bot.catch((err, ctx) => {
  console.error(`Telegram Bot Error encountered for ${ctx.updateType}:`, err);
});

// Start bot if BOT_TOKEN is configured
if (BOT_TOKEN && BOT_TOKEN !== 'dummy:token') {
  bot.launch().then(() => {
    console.log('✅ Telegram Bot is running and listening for updates!');
  }).catch((err) => {
    console.error('❌ Failed to launch Telegram Bot:', err.message);
  });

  // Enable graceful stop
  process.once('SIGINT', () => bot.stop('SIGINT'));
  process.once('SIGTERM', () => bot.stop('SIGTERM'));
} else {
  console.log('ℹ️  Bot created in configuration standby mode.');
  console.log('ℹ️  Add your TELEGRAM_BOT_TOKEN to ../.env or telegram-bot/.env to launch the live polling.');
}

module.exports = bot;
