const { Markup } = require('telegraf');
const { getPosts } = require('../api');
const { getBackToMenuKeyboard, getInfoKeyboard } = require('../keyboards');

/**
 * Handle Info menu - list published posts/news from database with title and short description,
 * and a "View full" button linking to the post/news URL.
 */
async function handleInfo(ctx) {
  try {
    const data = await getPosts();
    const posts = data.posts || [];

    if (posts.length === 0) {
      const text = `ℹ️ *Information & Announcements*\n━━━━━━━━━━━━━━━━━━━━\nNo news or updates posted yet. Check back soon!`;
      const keyboard = Markup.inlineKeyboard([
        [Markup.button.url('💬 Help', 'https://t.me/xingzhengx')],
        [Markup.button.callback('🏠 Main Menu', 'main_menu')],
      ]);

      if (ctx.callbackQuery) {
        try {
          return await ctx.editMessageText(text, { parse_mode: 'Markdown', ...keyboard });
        } catch (e) {
          // Fallback to reply
        }
      }
      return ctx.reply(text, { parse_mode: 'Markdown', ...keyboard });
    }

    let text = `ℹ️ *XUNDEFINED INFORMATION & NEWS*\n`;
    text += `━━━━━━━━━━━━━━━━━━━━\n\n`;

    posts.forEach((p, idx) => {
      const categoryTag = p.category ? `[${p.category.toUpperCase()}] ` : '';
      const cleanTitle = p.title.replace(/[\[\]]/g, '');
      text += `📢 *${idx + 1}.* [${categoryTag}${cleanTitle}](${p.url})\n`;
      text += `${p.short_description}\n\n`;
    });

    const keyboard = getInfoKeyboard();

    if (ctx.callbackQuery) {
      try {
        return await ctx.editMessageText(text, {
          parse_mode: 'Markdown',
          disable_web_page_preview: true,
          ...keyboard,
        });
      } catch (e) {
        // Fallback to reply
      }
    }

    await ctx.reply(text, {
      parse_mode: 'Markdown',
      disable_web_page_preview: true,
      ...keyboard,
    });
  } catch (err) {
    console.error('handleInfo error:', err);
    await ctx.reply(`❌ Failed to retrieve information: ${err.message}`, {
      ...getBackToMenuKeyboard(),
    });
  }
}

module.exports = {
  handleInfo,
};
