const { Markup } = require('telegraf');
const { getActivities } = require('../api');
const { getBackToMenuKeyboard } = require('../keyboards');

/**
 * Handle activities menu
 */
async function handleActivities(ctx) {
  try {
    const data = await getActivities(ctx.from.id);
    const activities = data.activities || [];

    if (activities.length === 0) {
      const text = `📊 *Account Activities*\n━━━━━━━━━━━━━━━━━━━━\nNo recent activity found for your account.`;
      return ctx.reply(text, {
        parse_mode: 'Markdown',
        ...getBackToMenuKeyboard()
      });
    }

    let text = `📊 *Recent Account Activities*\n`;
    text += `━━━━━━━━━━━━━━━━━━━━\n\n`;

    activities.forEach((act, idx) => {
      let icon = '🔹';
      if (act.action === 'REGISTER') icon = '🎉';
      if (act.action === 'PURCHASE') icon = '🛒';
      if (act.action === 'BALANCE_TOPUP') icon = '💰';
      if (act.action === 'BIND_DOMAIN') icon = '🌐';
      if (act.action === 'REMOVE_DOMAIN') icon = '🗑️';

      text += `${icon} *[${act.action}]* - ${act.date}\n`;
      text += `   ${act.description}\n\n`;
    });

    await ctx.reply(text, {
      parse_mode: 'Markdown',
      ...Markup.inlineKeyboard([
        [Markup.button.callback('🔄 Refresh Activities', 'menu_activities')],
        [Markup.button.callback('🏠 Main Menu', 'main_menu')]
      ])
    });
  } catch (err) {
    console.error('handleActivities error:', err);
    await ctx.reply(`❌ Failed to retrieve activities: ${err.message}`, {
      ...getBackToMenuKeyboard()
    });
  }
}

module.exports = {
  handleActivities,
};
