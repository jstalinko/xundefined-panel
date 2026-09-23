const { Markup } = require('telegraf');
const { getOrders } = require('../api');
const { getBackToMenuKeyboard } = require('../keyboards');

/**
 * Handle orders menu
 */
async function handleOrders(ctx) {
  try {
    const data = await getOrders(ctx.from.id);
    const orders = data.orders || [];

    if (orders.length === 0) {
      const text = `📋 *Order History*\n━━━━━━━━━━━━━━━━━━━━\nYou have no orders recorded yet.\n\nReady to get your first website script?`;
      return ctx.reply(text, {
        parse_mode: 'Markdown',
        ...Markup.inlineKeyboard([
          [Markup.button.callback('🛍️ Browse Products', 'menu_products')],
          [Markup.button.callback('🏠 Main Menu', 'main_menu')]
        ])
      });
    }

    let text = `📋 *Your Order History*\n`;
    text += `━━━━━━━━━━━━━━━━━━━━\n\n`;

    orders.forEach((ord, idx) => {
      const statusIcon = ord.status === 'completed' ? '✅' : '⏳';
      const orderRef = ord.invoice || ord.order_number || '-';
      const isTopup = orderRef.startsWith('TOPUP-')
        || (ord.order_number && ord.order_number.startsWith('TOPUP-'))
        || ord.product_name === 'TOPUP BALANCE'
        || ord.product_name === 'Unknown Script'
        || (ord.product_name && ord.product_name.toLowerCase().includes('topup'));
      const productName = isTopup ? 'TOPUP BALANCE' : (ord.product_name || 'Website Script');
      const paymentMethod = ord.payment_method || ord.payment_currency || 'Balance';
      text += `*${idx + 1}. Order:* \`${orderRef}\`\n`;
      text += `• *Product:* ${productName}\n`;
      text += `• *Payment Method:* \`${paymentMethod}\`\n`;
      text += `• *Amount:* *$${Number(ord.amount).toFixed(2)} USD*\n`;
      text += `• *Status:* ${statusIcon} \`${ord.status.toUpperCase()}\`\n`;
      text += `• *Date:* ${ord.created_at}\n\n`;
    });

    const keyboard = Markup.inlineKeyboard([
      [Markup.button.callback('📥 Go to Downloads', 'menu_download')],
      [Markup.button.callback('🛍️ Shop More', 'menu_products')],
      [Markup.button.callback('🏠 Main Menu', 'main_menu')]
    ]);

    if (ctx.callbackQuery) {
      try {
        await ctx.editMessageText(text, {
          parse_mode: 'Markdown',
          ...keyboard,
        });
        return;
      } catch (e) {
        // Fallback to reply
      }
    }

    await ctx.reply(text, {
      parse_mode: 'Markdown',
      ...keyboard,
    });
  } catch (err) {
    console.error('handleOrders error:', err);
    await ctx.reply(`❌ Failed to retrieve orders: ${err.message}`, {
      ...getBackToMenuKeyboard()
    });
  }
}

module.exports = {
  handleOrders,
};
