const { Markup } = require('telegraf');

/**
 * Main Persistent Reply Keyboard
 */
function getMainMenuKeyboard() {
  return Markup.keyboard([
    ['🛍️ Products', '📥 Download'],
    ['📋 Orders', '📊 Activities'],
    ['🌐 Domains', '👤 Profile'],
    ['💳 Balance', 'ℹ️ Info']
  ]).resize();
}

/**
 * Main Inline Keyboard
 */
function getMainInlineKeyboard() {
  return Markup.inlineKeyboard([
    [
      Markup.button.callback('🛍️ Products', 'menu_products'),
      Markup.button.callback('📥 Download', 'menu_download'),
    ],
    [
      Markup.button.callback('📋 Orders', 'menu_orders'),
      Markup.button.callback('📊 Activities', 'menu_activities'),
    ],
    [
      Markup.button.callback('🌐 Domains', 'menu_domains'),
      Markup.button.callback('👤 Profile', 'menu_profile'),
    ],
    [
      Markup.button.callback('💳 Balance', 'menu_balance'),
      Markup.button.callback('ℹ️ Info', 'menu_info'),
    ],
    [
      Markup.button.url('💬 Help', 'https://t.me/xingzhengx'),
    ]
  ]);
}

/**
 * Product detail keyboard with action button to buy
 */
function getProductDetailKeyboard(product) {
  const buttons = [];

  buttons.push([
    Markup.button.callback(`🛒 Buy Product ($${Number(product.price).toFixed(2)})`, `prod_confirm_buy_${product.id}`)
  ]);

  buttons.push([
    Markup.button.callback('« Back to Products', 'menu_products'),
    Markup.button.callback('🏠 Main Menu', 'main_menu')
  ]);

  return Markup.inlineKeyboard(buttons);
}

/**
 * Confirm Purchase Keyboard
 */
function getConfirmPurchaseKeyboard(productId, price) {
  return Markup.inlineKeyboard([
    [
      Markup.button.callback(`✅ Confirm Purchase ($${Number(price).toFixed(2)})`, `prod_do_buy_${productId}`),
      Markup.button.callback('❌ Cancel', `prod_view_${productId}`)
    ]
  ]);
}

/**
 * Back to Main Menu Inline Keyboard
 */
function getBackToMenuKeyboard() {
  return Markup.inlineKeyboard([
    [Markup.button.callback('🏠 Main Menu', 'main_menu')]
  ]);
}

/**
 * Balance action keyboard
 */
function getBalanceKeyboard() {
  return Markup.inlineKeyboard([
    [
      Markup.button.callback('🪙 Top Up Balance', 'bal_crypto_start'),
    ],
    [
      Markup.button.callback('🔄 Refresh Balance', 'menu_balance'),
      Markup.button.callback('🏠 Main Menu', 'main_menu')
    ]
  ]);
}

/**
 * Topup Amount Selection Keyboard: $60, $80, $100, $200
 */
function getCryptoAmountsKeyboard() {
  return Markup.inlineKeyboard([
    [
      Markup.button.callback('$60', 'bal_amt_60'),
      Markup.button.callback('$80', 'bal_amt_80'),
    ],
    [
      Markup.button.callback('$100', 'bal_amt_100'),
      Markup.button.callback('$200', 'bal_amt_200'),
    ],
    [
      Markup.button.callback('« Back to Balance', 'menu_balance'),
      Markup.button.callback('🏠 Main Menu', 'main_menu'),
    ]
  ]);
}

/**
 * Crypto Currency Selection Keyboard:
 * BTC, USDT ERC20, USDT TRC20, USDT SOL, SOLANA, ETH, Ltct(demo only), other payment (contact us )
 */
function getCryptoCoinsKeyboard(amount) {
  return Markup.inlineKeyboard([
    [
      Markup.button.callback('₿ BTC', `bal_coin_${amount}_BTC`),
      Markup.button.callback('💵 USDT ERC20', `bal_coin_${amount}_USDT.ERC20`),
    ],
    [
      Markup.button.callback('💵 USDT TRC20', `bal_coin_${amount}_USDT.TRC20`),
      Markup.button.callback('💵 USDT SOL', `bal_coin_${amount}_USDT.SOL`),
    ],
    [
      Markup.button.callback('☀️ SOLANA', `bal_coin_${amount}_SOL`),
      Markup.button.callback('⟠ ETH', `bal_coin_${amount}_ETH`),
    ],
    [
      Markup.button.callback('⚡ Ltct(demo only)', `bal_coin_${amount}_LTCT`),
    ],
    [
      Markup.button.callback('💬 other payment (contact us )', 'bal_other_payment'),
    ],
    [
      Markup.button.callback('« Choose Different Amount', 'bal_crypto_start'),
      Markup.button.callback('🏠 Main Menu', 'main_menu'),
    ]
  ]);
}

/**
 * Crypto Invoice / Payment Status Keyboard
 */
function getCryptoInvoiceKeyboard(invoice, paymentUrl, isDemo = false, amount = null) {
  const buttons = [];

  if (isDemo && amount) {
    buttons.push([
      Markup.button.callback(`⚡ Complete Demo Payment (+$${amount})`, `bal_topup_demo_${amount}`)
    ]);
  }

  if (paymentUrl) {
    buttons.push([Markup.button.url('🌐 Open Web Gateway', paymentUrl)]);
  }

  buttons.push([
    Markup.button.callback('🔄 Check On-Chain Status', `bal_chk_${invoice}`)
  ]);

  buttons.push([
    Markup.button.callback('« Back to Balance', 'menu_balance'),
    Markup.button.callback('🏠 Main Menu', 'main_menu')
  ]);

  return Markup.inlineKeyboard(buttons);
}

/**
 * Domains action keyboard
 */
function getDomainsKeyboard(domains = []) {
  const rows = [];
  
  rows.push([
    Markup.button.callback('🔄 Refresh', 'menu_domains'),
  ]);

  if (domains.length > 0) {
    domains.forEach(d => {
      rows.push([
        Markup.button.callback(`🗑️ Remove ${d.domain}`, `dom_del_${d.id}`)
      ]);
    });
  }

  rows.push([Markup.button.callback('🏠 Main Menu', 'main_menu')]);

  return Markup.inlineKeyboard(rows);
}

/**
 * Activities action keyboard
 */
function getActivitiesKeyboard(hasActivities = true) {
  const rows = [];

  if (hasActivities) {
    rows.push([
      Markup.button.callback('📄 Download All as TXT', 'act_download_txt'),
    ]);
  }

  rows.push([
    Markup.button.callback('🔄 Refresh Activities', 'menu_activities'),
    Markup.button.callback('🏠 Main Menu', 'main_menu'),
  ]);

  return Markup.inlineKeyboard(rows);
}

/**
 * Purchased products keyboard: showing button for each purchased product
 */
function getPurchasedProductsKeyboard(downloads = []) {
  const buttons = [];
  const seen = new Set();

  downloads.forEach((item) => {
    if (item.product_id && !seen.has(item.product_id)) {
      seen.add(item.product_id);
      buttons.push([
        Markup.button.callback(`📦 ${item.product_name}`, `dl_prod_${item.product_id}`)
      ]);
    }
  });

  buttons.push([Markup.button.callback('🏠 Main Menu', 'main_menu')]);
  return Markup.inlineKeyboard(buttons);
}

/**
 * Product versions keyboard: showing button "product_name - version"
 */
function getProductVersionsKeyboard(productId, productName, versions = []) {
  const buttons = [];

  versions.forEach((v) => {
    const ver = v.version || '1.0.0';
    buttons.push([
      Markup.button.callback(`${productName} - ${ver}`, `dl_file_${productId}_${ver}`)
    ]);
  });

  buttons.push([
    Markup.button.callback('« Back to Downloads', 'menu_download'),
    Markup.button.callback('🏠 Main Menu', 'main_menu')
  ]);

  return Markup.inlineKeyboard(buttons);
}

/**
 * Info / News Keyboard with "View full" URL buttons
 */
function getInfoKeyboard(posts = []) {
  const rows = [];

  posts.forEach((p, idx) => {
    const label = posts.length === 1
      ? '🔗 View full'
      : `🔗 View full: ${p.title.length > 20 ? p.title.slice(0, 18) + '...' : p.title}`;
    rows.push([Markup.button.url(label, p.url)]);
  });

  rows.push([
    Markup.button.callback('🔄 Refresh Info', 'menu_info'),
    Markup.button.callback('🏠 Main Menu', 'main_menu'),
  ]);

  return Markup.inlineKeyboard(rows);
}

module.exports = {
  getMainMenuKeyboard,
  getMainInlineKeyboard,
  getProductDetailKeyboard,
  getConfirmPurchaseKeyboard,
  getBackToMenuKeyboard,
  getBalanceKeyboard,
  getCryptoAmountsKeyboard,
  getCryptoCoinsKeyboard,
  getCryptoInvoiceKeyboard,
  getDomainsKeyboard,
  getActivitiesKeyboard,
  getPurchasedProductsKeyboard,
  getProductVersionsKeyboard,
  getInfoKeyboard,
};

