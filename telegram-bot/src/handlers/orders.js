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
      text += `*${idx + 1}. Order:* \`${ord.order_number}\`\n`;
      text += `• *Product:* ${ord.product_name}\n`;
      text += `• *Amount:* *$${Number(ord.amount).toFixed(2)} USD*\n`;
      text += `• *Status:* ${statusIcon} \`${ord.status.toUpperCase()}\`\n`;
      text += `• *Date:* ${ord.created_at}\n\n`;
    });

    await ctx.reply(text, {
      parse_mode: 'Markdown',
      ...Markup.inlineKeyboard([
        [Markup.button.callback('📥 Go to Downloads', 'menu_download')],
        [Markup.button.callback('🛍️ Shop More', 'menu_products')],
        [Markup.button.callback('🏠 Main Menu', 'main_menu')]
      ])
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
