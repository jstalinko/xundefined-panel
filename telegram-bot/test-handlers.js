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

async function testHandlers() {
  console.log('Testing Telegram Bot UI & Message Handlers...');

  let lastOutput = null;
  const mockCtx = {
    from: { id: '123456789', first_name: 'Alex' },
    state: { user: { name: 'Alex', balance: 315 } },
    callbackQuery: { id: 'cb_1' },
    answerCbQuery: async () => {},
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
  console.log('Products Text:\n', lastOutput.text);
  const prodButtons = lastOutput.extra.reply_markup.inline_keyboard;
  console.log('Product Buttons Count:', prodButtons.length);
  console.log('Sample Button:', prodButtons[0][0].text);
  if (!prodButtons[0][0].text.includes('$')) {
    throw new Error('Product button should show title and price!');
  }
  if (lastOutput.text.includes('File:')) {
    throw new Error('handleProducts should NOT show full file/details in the list text!');
  }

  // Extract real product ID from button callback
  const firstProdCallback = prodButtons[0][0].callback_data;
  const sampleProdId = firstProdCallback.replace('prod_view_', '');
  console.log(`Using real Product ID: ${sampleProdId}`);

  // 2. Test handleProductDetail (after clicked)
  console.log('\n--- 2. Testing handleProductDetail ---');
  await handleProductDetail(mockCtx, sampleProdId);
  console.log('Detail Text:\n', lastOutput.text);
  const detailButtons = lastOutput.extra.reply_markup.inline_keyboard;
  console.log('Detail Buy Button:', detailButtons[0][0].text);

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

  // 3. Test handleDownload Flow (Multi-stage)
  console.log('\n--- 3. Testing handleDownload (Product Buttons Only) ---');
  await handleDownload(mockCtx);
  console.log('Download Menu Text:\n', lastOutput.text);
  const dlButtons = lastOutput.extra.reply_markup.inline_keyboard;
  console.log('Download Buttons:', dlButtons.map(r => r.map(b => b.text)));

  const purchasedProdButton = dlButtons.flat().find(b => b.callback_data && b.callback_data.startsWith('dl_prod_'));
  if (!purchasedProdButton) {
    throw new Error('Download vault should show buttons for purchased products!');
  }
  console.log('Found Purchased Product Button:', purchasedProdButton.text, '->', purchasedProdButton.callback_data);

  const purchasedProdId = purchasedProdButton.callback_data.replace('dl_prod_', '');

  // 3.2 Test handleDownloadVersions ("product_name - version")
  console.log(`\n--- 3.2 Testing handleDownloadVersions for Product ${purchasedProdId} ---`);
  await handleDownloadVersions(mockCtx, purchasedProdId);
  console.log('Versions Text:\n', lastOutput.text);
  const verButtons = lastOutput.extra.reply_markup.inline_keyboard;
  const verTexts = verButtons.flat().map(b => b.text);
  console.log('Version Buttons:', verTexts);

  const versionButton = verButtons.flat().find(b => b.callback_data && b.callback_data.startsWith('dl_file_'));
  if (!versionButton) {
    throw new Error('Should show version button matching "product_name - version"');
  }
  if (!versionButton.text.includes(' - ')) {
    throw new Error('Version button must be formatted as "product_name - version"');
  }
  console.log('Selected Version Button:', versionButton.text, '->', versionButton.callback_data);

  // Parse dl_file_<prodId>_<version>
  const match = versionButton.callback_data.match(/^dl_file_(\d+)_(.+)$/);
  const versionToDownload = match ? match[2] : '1.0.0';

  // 3.3 Test handleSendDownloadFile (Sends attachment & details)
  console.log(`\n--- 3.3 Testing handleSendDownloadFile for Version ${versionToDownload} ---`);
  await handleSendDownloadFile(mockCtx, purchasedProdId, versionToDownload);
  const outputText = lastOutput.text || (lastOutput.extra && lastOutput.extra.caption) || '';
  console.log('Download Delivery Details:\n', outputText);

  if (!outputText.includes('Product Name:')) {
    throw new Error('Delivery details must include Product Name!');
  }
  if (!outputText.includes('Version:')) {
    throw new Error('Delivery details must include Version!');
  }
  if (!outputText.includes('Filename:')) {
    throw new Error('Delivery details must include Filename!');
  }
  if (!outputText.includes('MD5 Checksum:')) {
    throw new Error('Delivery details must include MD5 Checksum!');
  }

  // 4. Test Top-Up Balance Flow: Options $60, $80, $100, $200
  console.log('\n--- 4. Testing Top-Up Options ($60, $80, $100, $200) ---');
  await handleCryptoStart(mockCtx);
  const amtButtons = lastOutput.extra.reply_markup.inline_keyboard;
  const amtTexts = amtButtons.flat().map(b => b.text);
  console.log('Topup Amount Buttons:', amtTexts);
  if (!amtTexts.includes('$60') || !amtTexts.includes('$80') || !amtTexts.includes('$100') || !amtTexts.includes('$200')) {
    throw new Error('Topup options must include $60, $80, $100, $200!');
  }

  // 5. Test Crypto currency selection: BTC, USDT ERC20, USDT TRC20, USDT SOL, SOLANA, ETH, Ltct(demo only), other payment (contact us )
  console.log('\n--- 5. Testing Cryptocurrency Selection ---');
  await handleCryptoSelectAmount(mockCtx, 60);
  const coinButtons = lastOutput.extra.reply_markup.inline_keyboard;
  const coinTexts = coinButtons.flat().map(b => b.text);
  console.log('Crypto Coins Available:', coinTexts);

  const requiredCoins = [
    'BTC',
    'USDT ERC20',
    'USDT TRC20',
    'USDT SOL',
    'SOLANA',
    'ETH',
    'Ltct(demo only)',
    'other payment (contact us )'
  ];

  for (const req of requiredCoins) {
    const found = coinTexts.some(t => t.toLowerCase().includes(req.toLowerCase()));
    if (!found) {
      throw new Error(`Missing expected crypto or option: ${req}`);
    }
  }

  // 6. Test other payment (contact us)
  console.log('\n--- 6. Testing other payment (contact us) ---');
  await handleOtherPayment(mockCtx);
  console.log('Other Payment Output:\n', lastOutput.text);
  if (!lastOutput.text.includes('@XundefinedAdmin')) {
    throw new Error('Other payment should include contact support details!');
  }

  console.log('\n✅ ALL HANDLER TESTS PASSED WITH 100% COMPLIANCE!');
}

testHandlers().catch((err) => {
  console.error('\n❌ HANDLER TEST FAILED:', err.message);
  process.exit(1);
});
