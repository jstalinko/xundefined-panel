const { Markup } = require('telegraf');
const { getBalance, topupDemo, createTopupCrypto, checkTopupStatus } = require('../api');
const {
  getBalanceKeyboard,
  getCryptoAmountsKeyboard,
  getCryptoCoinsKeyboard,
  getCryptoInvoiceKeyboard,
  getBackToMenuKeyboard,
} = require('../keyboards');

/**
 * Handle balance menu with concise cyber aesthetic.
 */
async function handleBalance(ctx) {
  try {
    const data = await getBalance(ctx.from.id);
    const balance = Number(data.balance).toFixed(2);
    const user = ctx.state.user;
    const username = (user && user.telegram_username)
      ? `@${user.telegram_username}`
      : (ctx.from.username ? `@${ctx.from.username}` : (user ? user.name : (ctx.from.first_name || 'User')));

    let text = `💳 *ACCOUNT BALANCE*\n`;
    text += `━━━━━━━━━━━━━━━━━━━━\n`;
    text += `• *Username:* ${username}\n`;
    text += `• *Telegram ID:* \`${ctx.from.id}\`\n`;
    text += `• *Balance:* \`$${balance} USD\`\n`;
    text += `━━━━━━━━━━━━━━━━━━━━\n`;
    text += `Automated crypto deposit:`;

    const keyboard = getBalanceKeyboard();

    if (ctx.callbackQuery) {
      await ctx.editMessageText(text, {
        parse_mode: 'Markdown',
        ...keyboard,
      });
    } else {
      await ctx.reply(text, {
        parse_mode: 'Markdown',
        ...keyboard,
      });
    }
  } catch (err) {
    console.error('handleBalance error:', err);
    await ctx.reply(`❌ Failed to retrieve balance: ${err.message}`, {
      ...getBackToMenuKeyboard(),
    });
  }
}

/**
 * Ask how much to topup: options $60, $80, $100, $200.
 */
async function handleCryptoStart(ctx) {
  let text = `🪙 *TOP-UP BALANCE*\n`;
  text += `━━━━━━━━━━━━━━━━━━━━\n`;
  text += `How much would you like to top up?\n`;
  text += `Select a topup option below:`;

  const keyboard = getCryptoAmountsKeyboard();

  if (ctx.callbackQuery) {
    await ctx.editMessageText(text, {
      parse_mode: 'Markdown',
      ...keyboard,
    });
  } else {
    await ctx.reply(text, {
      parse_mode: 'Markdown',
      ...keyboard,
    });
  }
}

/**
 * Straight showing cryptocurrency options after asking how much to topup:
 * BTC, USDT ERC20, USDT TRC20, USDT SOL, SOLANA, ETH, Ltct(demo only), other payment (contact us )
 */
async function handleCryptoSelectAmount(ctx, amount) {
  let text = `🪙 *SELECT CRYPTOCURRENCY*\n`;
  text += `━━━━━━━━━━━━━━━━━━━━\n`;
  text += `• *Top-Up Amount:* \`$${amount}.00 USD\`\n`;
  text += `━━━━━━━━━━━━━━━━━━━━\n`;
  text += `Select cryptocurrency for payment:`;

  const keyboard = getCryptoCoinsKeyboard(amount);

  await ctx.editMessageText(text, {
    parse_mode: 'Markdown',
    ...keyboard,
  });
}

/**
 * Handle alternative payment methods (contact us).
 */
async function handleOtherPayment(ctx) {
  let text = `💬 *OTHER PAYMENT METHODS*\n`;
  text += `━━━━━━━━━━━━━━━━━━━━\n`;
  text += `To pay using alternative payment methods (PayPal, Credit Card, Bank Transfer, QRIS, Wire, etc.):\n\n`;
  text += `Please reach out to our team directly:\n`;
  text += `👉 *Telegram Support:* @XundefinedAdmin\n\n`;
  text += `Our team will provide payment coordinates and credit your account promptly.`;

  const keyboard = Markup.inlineKeyboard([
    [Markup.button.url('💬 Contact Admin (@XundefinedAdmin)', 'https://t.me/XundefinedAdmin')],
    [Markup.button.callback('« Choose Topup Amount', 'bal_crypto_start')],
    [Markup.button.callback('🏠 Main Menu', 'main_menu')],
  ]);

  if (ctx.callbackQuery) {
    await ctx.editMessageText(text, {
      parse_mode: 'Markdown',
      ...keyboard,
    });
  } else {
    await ctx.reply(text, {
      parse_mode: 'Markdown',
      ...keyboard,
    });
  }
}

/**
 * Generate CoinPayments invoice and present deposit address.
 */
async function handleCryptoGenerateInvoice(ctx, amount, currency) {
  try {
    await ctx.answerCbQuery('Generating deposit address...');

    const isDemo = (currency === 'LTCT');
    const res = await createTopupCrypto(ctx.from.id, amount, currency);
    const txn = res.transaction || {};
    const invoice = txn.invoice || res.order?.invoice;
    const cryptoAmt = txn.payment_amount || amount;
    const address = txn.payment_address || 'Pending Address';
    const paymentUrl = txn.payment_url || null;

    let text = isDemo ? `⚡ *DEMO PAYMENT INVOICE (LTCT)*\n` : `🪙 *COINPAYMENTS INVOICE CREATED*\n`;
    text += `━━━━━━━━━━━━━━━━━━━━\n`;
    text += `• *Invoice:* \`#${invoice}\`\n`;
    text += `• *Top-Up Amount:* \`$${amount}.00 USD\`\n`;
    text += `• *Send Amount:* \`${cryptoAmt} ${currency}\`\n`;
    text += `• *Receiving Address:*\n\`${address}\`\n`;
    if (txn.payment_dest_tag) {
      text += `• *Memo / Dest Tag:* \`${txn.payment_dest_tag}\`\n`;
    }
    text += `━━━━━━━━━━━━━━━━━━━━\n`;
    if (isDemo) {
      text += `💡 *DEMO MODE:* You can send testnet LTCT or click instant demo payment below.`;
    } else {
      text += `⚠️ Send the exact crypto amount specified above.\n`;
      text += `Upon blockchain confirmation, your account will be credited automatically.`;
    }

    const keyboard = getCryptoInvoiceKeyboard(invoice, paymentUrl, isDemo, amount);

    await ctx.editMessageText(text, {
      parse_mode: 'Markdown',
      disable_web_page_preview: true,
      ...keyboard,
    });
  } catch (err) {
    console.error('handleCryptoGenerateInvoice error:', err);

    if (err.message && err.message.includes('Receiver does not accept that coin')) {
      const noticeText = `⚠️ *Cryptocurrency Gateway Notice*\n` +
        `━━━━━━━━━━━━━━━━━━━━\n` +
        `• *Requested Asset:* \`${currency}\`\n` +
        `• *Status:* Not currently enabled in merchant's CoinPayments wallet.\n\n` +
        `👉 *Active Instant Deposit Options:*\n` +
        `• 💵 *USDT ERC20* (Ethereum)\n` +
        `• 💵 *USDT SOL* (Solana)\n` +
        `• ☀️ *SOLANA (SOL)*\n` +
        `• ⟠ *ETHEREUM (ETH)*\n` +
        `• ⚡ *LTCT (Demo Testnet)*\n\n` +
        `To pay with direct *${currency}*, contact our team directly:`;

      const fallbackKeyboard = Markup.inlineKeyboard([
        [
          Markup.button.callback('💵 Pay USDT ERC20', `bal_coin_${amount}_USDT.ERC20`),
          Markup.button.callback('💵 Pay USDT SOL', `bal_coin_${amount}_USDT.SOL`),
        ],
        [
          Markup.button.callback('☀️ Pay SOLANA', `bal_coin_${amount}_SOL`),
          Markup.button.callback('⟠ Pay ETH', `bal_coin_${amount}_ETH`),
        ],
        [
          Markup.button.callback(`💬 Contact Admin for ${currency}`, 'bal_other_payment'),
        ],
        [
          Markup.button.callback('« Choose Different Amount', 'bal_crypto_start'),
          Markup.button.callback('🏠 Main Menu', 'main_menu'),
        ],
      ]);

      if (ctx.callbackQuery) {
        return ctx.editMessageText(noticeText, {
          parse_mode: 'Markdown',
          ...fallbackKeyboard,
        });
      }
      return ctx.reply(noticeText, {
        parse_mode: 'Markdown',
        ...fallbackKeyboard,
      });
    }

    const errorMsg = `❌ Failed to initialize CoinPayments: ${err.message}`;
    if (ctx.callbackQuery) {
      await ctx.editMessageText(errorMsg, { ...getBackToMenuKeyboard() });
    } else {
      await ctx.reply(errorMsg, { ...getBackToMenuKeyboard() });
    }
  }
}

/**
 * Check payment status for a specific invoice.
 */
async function handleCheckTopupStatus(ctx, invoice) {
  try {
    await ctx.answerCbQuery('Checking on-chain status...');
    const data = await checkTopupStatus(invoice, true);

    let text = `📡 *PAYMENT STATUS TELEMETRY*\n`;
    text += `━━━━━━━━━━━━━━━━━━━━\n`;
    text += `• *Invoice:* \`#${data.invoice}\`\n`;
    text += `• *Asset:* \`${data.payment_currency || 'CRYPTO'}\`\n`;
    text += `• *Status:* \`${String(data.status).toUpperCase()}\`\n`;

    if (data.is_completed) {
      text += `━━━━━━━━━━━━━━━━━━━━\n`;
      text += `✅ *PAYMENT CONFIRMED!*\n`;
      text += `• *New Balance:* \`$${Number(data.new_balance || 0).toFixed(2)} USD\`\n`;
      text += `Your balance has been credited successfully.`;

      await ctx.editMessageText(text, {
        parse_mode: 'Markdown',
        ...Markup.inlineKeyboard([
          [Markup.button.callback('🛍️ Browse Products', 'menu_products')],
          [Markup.button.callback('💳 Check Balance', 'menu_balance')],
          [Markup.button.callback('🏠 Main Menu', 'main_menu')],
        ]),
      });
      return;
    }

    if (data.is_processing) {
      text += `━━━━━━━━━━━━━━━━━━━━\n`;
      text += `🔄 *TRANSACTION DETECTED*\n`;
      text += `Awaiting required block confirmations on the blockchain...`;
    } else if (data.is_cancelled) {
      text += `━━━━━━━━━━━━━━━━━━━━\n`;
      text += `❌ *PAYMENT EXPIRED OR CANCELLED*`;
    } else {
      text += `━━━━━━━━━━━━━━━━━━━━\n`;
      text += `⏳ *AWAITING ON-CHAIN TRANSFER*\n`;
      text += `Address: \`${data.payment_address || 'N/A'}\`\n`;
      text += `Send: \`${data.payment_amount || ''} ${data.payment_currency || ''}\``;
    }

    const keyboard = getCryptoInvoiceKeyboard(invoice, data.redirect_url);

    await ctx.editMessageText(text, {
      parse_mode: 'Markdown',
      disable_web_page_preview: true,
      ...keyboard,
    });
  } catch (err) {
    console.error('handleCheckTopupStatus error:', err);
    await ctx.reply(`❌ Status check error: ${err.message}`);
  }
}

/**
 * Handle demo balance top-up
 */
async function handleDemoTopup(ctx, amount) {
  try {
    const result = await topupDemo(ctx.from.id, amount);
    await ctx.answerCbQuery(`Added +$${amount}!`);
    await ctx.reply(`🎉 *Balance Top-Up Successful!*\n\nAdded \`+$${amount}.00 USD\`.\nNew Balance: \`$${Number(result.new_balance).toFixed(2)} USD\``, {
      parse_mode: 'Markdown',
      ...Markup.inlineKeyboard([
        [Markup.button.callback('🛍️ Shop Products', 'menu_products')],
        [Markup.button.callback('💳 Check Balance', 'menu_balance')],
        [Markup.button.callback('🏠 Main Menu', 'main_menu')],
      ]),
    });
  } catch (err) {
    console.error('handleDemoTopup error:', err);
    await ctx.reply(`❌ Top-up failed: ${err.message}`);
  }
}

module.exports = {
  handleBalance,
  handleCryptoStart,
  handleCryptoSelectAmount,
  handleOtherPayment,
  handleCryptoGenerateInvoice,
  handleCheckTopupStatus,
  handleDemoTopup,
};
