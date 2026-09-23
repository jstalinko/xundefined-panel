const { getMainMenuKeyboard, getMainInlineKeyboard } = require('../keyboards');

/**
 * Handle /start command or main menu greeting
 */
async function handleStart(ctx) {
  const user = ctx.state.user;
  const isNew = ctx.state.isNewUser;
  const name = user ? user.name : (ctx.from.first_name || 'User');
  const balance = user ? Number(user.balance).toFixed(2) : '0.00';
  const role = user && user.role ? String(user.role).toUpperCase() : 'USER';
  const ordersCount = user ? (user.orders_count || 0) : 0;
  const domainsCount = user ? (user.domains_count || 0) : 0;

  const header = isNew
    ? `🎉 *Welcome to Xundefined Digital Products Store!*`
    : `✨ *Welcome Back, ${name}!*`;

  const statusNote = isNew
    ? `_Your account has been automatically registered via Laravel API._\n\n`
    : `\n`;

  const text = `${header}
${statusNote}👤 *User Profile & Account Summary:*
━━━━━━━━━━━━━━━━━━━━
• *Name:* ${name}
• *Telegram ID:* \`${ctx.from.id}\`
• *Username:* ${ctx.from.username ? `@${ctx.from.username}` : '_Not set_'}
• *Current Balance:* 💰 *$${balance} USD*
• *Purchased Scripts:* 📦 \`${ordersCount}\`
• *Active Domains:* 🌐 \`${domainsCount}\`
━━━━━━━━━━━━━━━━━━━━

🛒 *What would you like to do today?*
Choose an option below or use the quick menu buttons:`;

  if (ctx.callbackQuery) {
    try {
      return await ctx.editMessageText(text, {
        parse_mode: 'Markdown',
        ...getMainInlineKeyboard(),
      });
    } catch (e) {
      // Fallback to reply
    }
  }

  // Send persistent reply keyboard first or alongside message
  await ctx.reply(text, {
    parse_mode: 'Markdown',
    ...getMainInlineKeyboard(),
  });

  // Ensure persistent keyboard is visible
  if (ctx.message) {
    await ctx.reply('👇 Use the quick menu below anytime:', {
      ...getMainMenuKeyboard(),
    });
  }
}

module.exports = {
  handleStart,
};
