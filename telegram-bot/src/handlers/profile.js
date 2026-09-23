const { Markup } = require('telegraf');
const { getProfile } = require('../api');
const { getBackToMenuKeyboard } = require('../keyboards');

/**
 * Handle user profile menu
 */
async function handleProfile(ctx) {
  try {
    const data = await getProfile(ctx.from.id);
    const p = data.profile;

    let text = `👤 *User Profile & Account Info*\n`;
    text += `━━━━━━━━━━━━━━━━━━━━\n`;
    text += `• *Full Name:* ${p.name}\n`;
    text += `• *Telegram ID:* \`${p.telegram_id}\`\n`;
    text += `• *Username:* ${p.telegram_username ? `@${p.telegram_username}` : '_Not set_'}\n`;
    text += `• *Member Since:* ${p.member_since}\n`;
    text += `━━━━━━━━━━━━━━━━━━━━\n`;
    text += `💰 *Account Balance:* *$${Number(p.balance).toFixed(2)} USD*\n`;
    text += `📦 *Completed Orders:* \`${p.orders_count}\`\n`;
    text += `💵 *Total Spent:* *$${Number(p.total_spent).toFixed(2)} USD*\n`;
    text += `🌐 *Registered Domains:* \`${p.domains_count}\`\n`;
    text += `━━━━━━━━━━━━━━━━━━━━`;

    const keyboard = Markup.inlineKeyboard([
      [
        Markup.button.callback('💳 Manage Balance', 'menu_balance'),
        Markup.button.callback('📋 View Orders', 'menu_orders'),
      ],
      [
        Markup.button.callback('🌐 Manage Domains', 'menu_domains'),
        Markup.button.callback('📥 My Downloads', 'menu_download'),
      ],
      [
        Markup.button.url('💬 Help', 'https://t.me/xingzhengx'),
        Markup.button.callback('🏠 Main Menu', 'main_menu'),
      ],
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
    console.error('handleProfile error:', err);
    await ctx.reply(`❌ Failed to retrieve profile: ${err.message}`, {
      ...getBackToMenuKeyboard(),
    });
  }
}

module.exports = {
  handleProfile,
};
