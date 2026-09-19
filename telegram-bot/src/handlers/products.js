const { Markup } = require('telegraf');
const { getProducts, getProduct, buyProduct } = require('../api');
const { getProductDetailKeyboard, getConfirmPurchaseKeyboard, getBackToMenuKeyboard } = require('../keyboards');

/**
 * Handle listing products:
 * Just showing button title of product and price.
 */
async function handleProducts(ctx) {
  try {
    const data = await getProducts();
    const products = data.products || [];

    if (products.length === 0) {
      return ctx.reply('🛍️ No products currently active in the catalog.', {
        ...getBackToMenuKeyboard(),
      });
    }

    let text = `🛍️ *PRODUCT CATALOG*\n`;
    text += `━━━━━━━━━━━━━━━━━━━━\n`;
    text += `Select a product below to view details and purchase:`;

    const inlineButtons = products.map((prod) => {
      const price = Number(prod.price).toFixed(2);
      return [
        Markup.button.callback(`${prod.name} ($${price})`, `prod_view_${prod.id}`)
      ];
    });

    inlineButtons.push([Markup.button.callback('🏠 Main Menu', 'main_menu')]);

    if (ctx.callbackQuery) {
      await ctx.editMessageText(text, {
        parse_mode: 'Markdown',
        ...Markup.inlineKeyboard(inlineButtons),
      });
    } else {
      await ctx.reply(text, {
        parse_mode: 'Markdown',
        ...Markup.inlineKeyboard(inlineButtons),
      });
    }
  } catch (err) {
    console.error('handleProducts error:', err);
    await ctx.reply(`❌ Failed to load catalog: ${err.message}`, {
      ...getBackToMenuKeyboard(),
    });
  }
}

/**
 * Handle viewing specific product detail with description, contents, and action button to buy.
 */
async function handleProductDetail(ctx, productId) {
  try {
    const data = await getProduct(productId);
    const prod = data.product;

    if (!prod) {
      return ctx.reply('❌ Product package not found.', { ...getBackToMenuKeyboard() });
    }

    const price = Number(prod.price).toFixed(2);
    const contents = Array.isArray(prod.contents) ? prod.contents : [];
    const totalVersions = contents.length > 0 ? contents.length : 1;

    let text = `📦 *${prod.name}*\n`;
    text += `━━━━━━━━━━━━━━━━━━━━\n`;
    text += `• *Price:* \`$${price} USD\`\n`;
    text += `• *Available Version:* (${totalVersions} Version available)\n\n`;

    if (prod.description) {
      const cleanDesc = prod.description.replace(/<[^>]*>?/gm, '').trim();
      text += `📄 *Description:*\n${cleanDesc}\n\n`;
    }

    text += `━━━━━━━━━━━━━━━━━━━━\n`;
    text += `Select the action button below to purchase:`;

    const keyboard = getProductDetailKeyboard(prod);

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
    console.error('handleProductDetail error:', err);
    await ctx.reply(`❌ Error loading product: ${err.message}`);
  }
}

/**
 * Handle purchase confirmation dialog.
 */
async function handleConfirmBuy(ctx, productId) {
  try {
    const data = await getProduct(productId);
    const prod = data.product;
    const user = ctx.state.user;
    const userBalance = user ? Number(user.balance) : 0;
    const prodPrice = Number(prod.price);

    let text = `🛒 *CONFIRM PURCHASE*\n`;
    text += `━━━━━━━━━━━━━━━━━━━━\n`;
    text += `• *Product:* ${prod.name} (v${prod.version || '1.0'})\n`;
    text += `• *Total Price:* \`$${prodPrice.toFixed(2)} USD\`\n`;
    text += `• *Your Balance:* \`$${userBalance.toFixed(2)} USD\`\n`;
    text += `━━━━━━━━━━━━━━━━━━━━\n`;

    if (userBalance < prodPrice) {
      const shortage = (prodPrice - userBalance).toFixed(2);
      text += `⚠️ *Insufficient Funds!*\n`;
      text += `You need \`$${shortage} USD\` more to purchase this product.\n\n`;
      text += `Top up your balance via automated crypto gateway:`;

      return ctx.editMessageText(text, {
        parse_mode: 'Markdown',
        ...Markup.inlineKeyboard([
          [Markup.button.callback('🪙 Top Up Balance', 'bal_crypto_start')],
          [Markup.button.callback('« Back to Product', `prod_view_${productId}`)],
        ]),
      });
    }

    text += `Deduct \`$${prodPrice.toFixed(2)} USD\` from your balance and acquire product?`;

    await ctx.editMessageText(text, {
      parse_mode: 'Markdown',
      ...getConfirmPurchaseKeyboard(productId, prodPrice),
    });
  } catch (err) {
    console.error('handleConfirmBuy error:', err);
    await ctx.reply(`❌ Error: ${err.message}`);
  }
}

/**
 * Execute actual purchase (without license key).
 */
async function handleExecuteBuy(ctx, productId) {
  try {
    const result = await buyProduct(ctx.from.id, productId);
    const order = result.order;
    const newBalance = Number(result.new_balance).toFixed(2);

    let text = `🎉 *PURCHASE COMPLETED*\n`;
    text += `━━━━━━━━━━━━━━━━━━━━\n`;
    text += `• *Order:* \`${order.order_number}\`\n`;
    text += `• *Product:* ${order.product_name} (v${order.version})\n`;
    if (order.file) {
      text += `• *Release File:* \`${order.file}\`\n`;
    }
    if (order.md5checksum) {
      text += `• *MD5 Hash:* \`${order.md5checksum}\`\n`;
    }
    text += `• *Paid:* \`$${Number(order.amount).toFixed(2)} USD\`\n`;
    text += `• *New Balance:* \`$${newBalance} USD\`\n`;
    text += `━━━━━━━━━━━━━━━━━━━━\n`;
    text += `💡 You can download all versions of this product in *Downloads* menu.`;

    await ctx.editMessageText(text, {
      parse_mode: 'Markdown',
      ...Markup.inlineKeyboard([
        [Markup.button.callback('📥 My Downloads', 'menu_download')],
        [Markup.button.callback('🌐 Manage Domains', 'menu_domains')],
        [Markup.button.callback('🏠 Main Menu', 'main_menu')],
      ]),
    });
  } catch (err) {
    console.error('handleExecuteBuy error:', err);
    const msg = err.data && err.data.message ? err.data.message : err.message;
    await ctx.reply(`❌ Purchase Failed: ${msg}`, {
      ...Markup.inlineKeyboard([
        [Markup.button.callback('🪙 Top Up Balance', 'bal_crypto_start')],
        [Markup.button.callback('« Back to Catalog', 'menu_products')],
      ]),
    });
  }
}

module.exports = {
  handleProducts,
  handleProductDetail,
  handleConfirmBuy,
  handleExecuteBuy,
};
