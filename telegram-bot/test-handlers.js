const { handleProducts, handleProductDetail, handleConfirmBuy } = require('./src/handlers/products');
const {
  handleDownload,
  handleDownloadVersions,
  handleSendDownloadFile,
} = require('./src/handlers/download');
const {
  handleBalance,
  handleCryptoStart,
  handleCryptoSelectAmount,
  handleOtherPayment,
} = require('./src/handlers/balance');
const { handleStart } = require('./src/handlers/start');
const { handleProfile } = require('./src/handlers/profile');
const { handleDomains } = require('./src/handlers/domains');
const { handleOrders } = require('./src/handlers/orders');
const { handleActivities, handleDownloadActivitiesTxt } = require('./src/handlers/activities');
const { handleInfo } = require('./src/handlers/info');

async function testHandlers() {
  console.log('Testing Telegram Bot UI & Message Handlers...');

  let lastOutput = null;
  const mockCtx = {
    from: { id: '123456789', first_name: 'Alex', username: 'digital_entrepreneur' },
    state: { user: { name: 'Alex Rivera', telegram_username: 'digital_entrepreneur', balance: 1000 } },
    callbackQuery: { id: 'cb_1' },
    answerCbQuery: async (msg) => { lastOutput = { ...(lastOutput || {}), answer: msg }; },
    reply: async (text, extra) => {
      lastOutput = { text, extra };
      return lastOutput;
    },
    editMessageText: async (text, extra) => {
      lastOutput = { text, extra };
      return lastOutput;
    },
    replyWithDocument: async (doc, extra) => {
      lastOutput = {
        document: doc,
        extra,
        text: extra ? extra.caption : '',
      };
      return lastOutput;
    },
  };

  // 1. Test handleProducts
  console.log('\n--- 1. Testing handleProducts ---');
  await handleProducts(mockCtx);
  const prodButtons = lastOutput.extra.reply_markup.inline_keyboard;
  console.log('Product Buttons Count:', prodButtons.length);
  if (!prodButtons[0][0].text.includes('$')) {
    throw new Error('Product button should show title and price!');
  }
  if (lastOutput.text.includes('File:')) {
    throw new Error('handleProducts should NOT show full file/details in the list text!');
  }

  const firstProdCallback = prodButtons[0][0].callback_data;
  const sampleProdId = firstProdCallback.replace('prod_view_', '');

  // 2. Test handleProductDetail
  console.log('\n--- 2. Testing handleProductDetail ---');
  await handleProductDetail(mockCtx, sampleProdId);
  const detailButtons = lastOutput.extra.reply_markup.inline_keyboard;
  if (!detailButtons[0][0].text.includes('Buy Product')) {
    throw new Error('Detail should have action button to buy!');
  }
  if (lastOutput.text.includes('Release File:') || lastOutput.text.includes('release file')) {
    throw new Error('Product detail must NOT show release file!');
  }
  if (lastOutput.text.includes('MD5 Checksum:') || lastOutput.text.includes('md5checksum')) {
    throw new Error('Product detail must NOT show md5checksum!');
  }
  if (!lastOutput.text.includes('Version available)')) {
    throw new Error('Product detail must show "(X Version available)" count!');
  }

  // 3. Test handleDownload Flow
  console.log('\n--- 3. Testing handleDownload (Download Purchased products) ---');
  await handleDownload(mockCtx);
  console.log('Download Text:\n', lastOutput.text);
  if (!lastOutput.text.includes('Download Purchased products')) {
    throw new Error('Download title must be "Download Purchased products"!');
  }
  if (lastOutput.text.includes('DOWNLOAD VAULT')) {
    throw new Error('DOWNLOAD VAULT must NOT be shown!');
  }

  const dlButtons = lastOutput.extra.reply_markup.inline_keyboard;
  const purchasedProdButton = dlButtons.flat().find(b => b.callback_data && b.callback_data.startsWith('dl_prod_'));
  if (!purchasedProdButton) {
    throw new Error('Download should show buttons for purchased products!');
  }
  const purchasedProdId = purchasedProdButton.callback_data.replace('dl_prod_', '');

  // 3.2 Test handleDownloadVersions
  console.log(`\n--- 3.2 Testing handleDownloadVersions for Product ${purchasedProdId} ---`);
  await handleDownloadVersions(mockCtx, purchasedProdId);
  const verButtons = lastOutput.extra.reply_markup.inline_keyboard;
  const versionButton = verButtons.flat().find(b => b.callback_data && b.callback_data.startsWith('dl_file_'));
  if (!versionButton || !versionButton.text.includes(' - ')) {
    throw new Error('Version button must be formatted as "product_name - version"');
  }

  const match = versionButton.callback_data.match(/^dl_file_(\d+)_(.+)$/);
  const versionToDownload = match ? match[2] : '1.0.0';

  // 3.3 Test handleSendDownloadFile
  console.log(`\n--- 3.3 Testing handleSendDownloadFile for Version ${versionToDownload} ---`);
  await handleSendDownloadFile(mockCtx, purchasedProdId, versionToDownload);
  const outputText = lastOutput.text || (lastOutput.extra && lastOutput.extra.caption) || '';
  if (!outputText.includes('Product Name:') || !outputText.includes('Version:') || !outputText.includes('Filename:') || !outputText.includes('MD5 Checksum:')) {
    throw new Error('Delivery details must include Product Name, Version, Filename, and MD5 Checksum!');
  }

  // 4. Test Top-Up Balance Flow: Options $60, $80, $100, $200
  console.log('\n--- 4. Testing Top-Up Options ($60, $80, $100, $200) ---');
  await handleCryptoStart(mockCtx);
  const amtButtons = lastOutput.extra.reply_markup.inline_keyboard;
  const amtTexts = amtButtons.flat().map(b => b.text);
  if (!amtTexts.includes('$60') || !amtTexts.includes('$80') || !amtTexts.includes('$100') || !amtTexts.includes('$200')) {
    throw new Error('Topup options must include $60, $80, $100, $200!');
  }

  // 5. Test Crypto currency selection
  console.log('\n--- 5. Testing Cryptocurrency Selection ---');
  await handleCryptoSelectAmount(mockCtx, 60);
  const coinButtons = lastOutput.extra.reply_markup.inline_keyboard;
  const coinTexts = coinButtons.flat().map(b => b.text);
  const requiredCoins = [
    'BTC', 'USDT ERC20', 'USDT TRC20', 'USDT SOL', 'SOLANA', 'ETH', 'Ltct(demo only)', 'other payment (contact us )'
  ];
  for (const req of requiredCoins) {
    if (!coinTexts.some(t => t.toLowerCase().includes(req.toLowerCase()))) {
      throw new Error(`Missing expected crypto or option: ${req}`);
    }
  }

  // 6. Test other payment (contact us)
  console.log('\n--- 6. Testing other payment (contact us) ---');
  await handleOtherPayment(mockCtx);
  if (!lastOutput.text.includes('@XundefinedAdmin')) {
    throw new Error('Other payment should include contact support details!');
  }

  // 7. Test Profile Handler
  console.log('\n--- 7. Testing Profile Handler ---');
  await handleProfile(mockCtx);
  console.log('Profile Output:\n', lastOutput.text);
  if (lastOutput.text.includes('Role:')) {
    throw new Error('Profile must NOT display Role!');
  }
  if (lastOutput.text.includes('All data synchronized in real-time with Laravel backend.')) {
    throw new Error('Profile must NOT display "All data synchronized in real-time with Laravel backend."!');
  }

  // 8. Test Balance Handler
  console.log('\n--- 8. Testing Balance Handler ---');
  await handleBalance(mockCtx);
  console.log('Balance Output:\n', lastOutput.text);
  if (lastOutput.text.includes('Operative')) {
    throw new Error('Balance must NOT display "Operative"!');
  }
  if (!lastOutput.text.includes('Username:')) {
    throw new Error('Balance must display "Username:"!');
  }
  if (lastOutput.text.includes('Automated crypto deposit via CoinPayments gateway:')) {
    throw new Error('Balance must NOT display "Automated crypto deposit via CoinPayments gateway:"!');
  }
  if (!lastOutput.text.includes('Automated crypto deposit:')) {
    throw new Error('Balance must display "Automated crypto deposit:"!');
  }

  // 9. Test Domains Handler
  console.log('\n--- 9. Testing Domains Handler ---');
  await handleDomains(mockCtx);
  console.log('Domains Output:\n', lastOutput.text);
  if (!lastOutput.text.includes('Manage Domains')) {
    throw new Error('Domains header must be "Manage Domains"!');
  }
  if (lastOutput.text.includes('Domain License Management')) {
    throw new Error('Domains header must NOT be "Domain License Management"!');
  }
  if (lastOutput.text.includes('Digital script websites require an authorized domain to function.')) {
    throw new Error('Domains must NOT include "Digital script websites require an authorized domain to function."!');
  }
  if (!lastOutput.text.includes('Domains.')) {
    throw new Error('Domains must display product quota (e.g., ... Domains.) at the top!');
  }
  if (!lastOutput.text.includes('Domains are registered automatically when you upload your script to your website and input your valid `account_key`')) {
    throw new Error('Domains must include automated registration instruction with account_key!');
  }
  const domButtons = lastOutput.extra.reply_markup.inline_keyboard.flat();
  if (domButtons.some(b => b.text.includes('Register Domain'))) {
    throw new Error('Domains keyboard must NOT include "Register Domain" button!');
  }

  // 10. Test Orders Handler
  console.log('\n--- 10. Testing Orders Handler ---');
  await handleOrders(mockCtx);
  console.log('Orders Output:\n', lastOutput.text);
  if (!lastOutput.text.includes('XUOR-') && !lastOutput.text.includes('TOPUP-')) {
    throw new Error('Orders must show order number or invoice with prefix XUOR- or TOPUP-!');
  }
  if (!lastOutput.text.includes('Payment Method:')) {
    throw new Error('Orders must show Payment Method!');
  }

  // 11. Test Activities Handler (Max 10 recents & Download TXT button)
  console.log('\n--- 11. Testing Activities Handler ---');
  await handleActivities(mockCtx);
  console.log('Activities Output:\n', lastOutput.text);
  const actButtons = lastOutput.extra.reply_markup.inline_keyboard.flat();
  const downloadTxtBtn = actButtons.find(b => b.callback_data === 'act_download_txt');
  if (!downloadTxtBtn) {
    throw new Error('Activities keyboard must include button to download all activities as TXT data!');
  }
  console.log('Found Download TXT Button:', downloadTxtBtn.text);

  // Count items displayed in text
  const activityMatches = lastOutput.text.match(/\[([A-Z_]+)\]/g) || [];
  console.log(`Activity entries displayed: ${activityMatches.length}`);
  if (activityMatches.length > 10) {
    throw new Error(`Activities list must NOT show more than 10 recent items (got ${activityMatches.length})!`);
  }

  // 12. Test handleDownloadActivitiesTxt
  console.log('\n--- 12. Testing handleDownloadActivitiesTxt ---');
  await handleDownloadActivitiesTxt(mockCtx);
  if (!lastOutput.document) {
    throw new Error('handleDownloadActivitiesTxt must send a document file!');
  }
  console.log('Sent Document Filename:', lastOutput.document.filename);
  if (!lastOutput.document.filename.endsWith('.txt')) {
    throw new Error('Exported activities document must be a .txt file!');
  }

  // 13. Test Start Handler Help Button (t.me/xingzhengx)
  console.log('\n--- 13. Testing Start Handler (/start) Help Button ---');
  await handleStart(mockCtx);
  console.log('Start Output:\n', lastOutput.text);
  const startButtons = lastOutput.extra.reply_markup.inline_keyboard.flat();
  const startHelpBtn = startButtons.find(b => b.url && b.url.includes('t.me/xingzhengx'));
  if (!startHelpBtn) {
    throw new Error('Start menu inline keyboard must include Help button going to t.me/xingzhengx!');
  }
  console.log('Found Start Help Button:', startHelpBtn.text, '->', startHelpBtn.url);

  // 14. Test Profile Handler Help Button (t.me/xingzhengx)
  console.log('\n--- 14. Testing Profile Handler Help Button ---');
  await handleProfile(mockCtx);
  const profileButtons = lastOutput.extra.reply_markup.inline_keyboard.flat();
  const profileHelpBtn = profileButtons.find(b => b.url && b.url.includes('t.me/xingzhengx'));
  if (!profileHelpBtn) {
    throw new Error('Profile menu inline keyboard must include Help button going to t.me/xingzhengx!');
  }
  console.log('Found Profile Help Button:', profileHelpBtn.text, '->', profileHelpBtn.url);

  // 15. Test Info Handler (Posts/News from DB with title links, no View full button)
  console.log('\n--- 15. Testing Info Handler (Posts/News from DB) ---');
  await handleInfo(mockCtx);
  console.log('Info Output:\n', lastOutput.text);
  if (!lastOutput.text.includes('INFORMATION & NEWS') && !lastOutput.text.includes('Information & Announcements')) {
    throw new Error('Info handler must display information & news header!');
  }
  if (!lastOutput.text.includes('](') || (!lastOutput.text.includes('/news/') && !lastOutput.text.includes('http'))) {
    throw new Error('Info titles must be formatted as markdown links to news URL!');
  }
  const infoButtons = lastOutput.extra.reply_markup.inline_keyboard.flat();
  const viewFullBtn = infoButtons.find(b => b.text && b.text.includes('View full'));
  if (viewFullBtn) {
    throw new Error('Info handler must NOT include "View full" URL button anymore; title must be a link!');
  }
  if (!lastOutput.extra.disable_web_page_preview) {
    throw new Error('Info handler must set disable_web_page_preview: true!');
  }
  console.log('Info verified: titles are clickable links, "View full" button removed.');

  console.log('\n✅ ALL 15 HANDLER TESTS PASSED WITH 100% COMPLIANCE!');
}

testHandlers().catch((err) => {
  console.error('\n❌ HANDLER TEST FAILED:', err.message);
  process.exit(1);
});
