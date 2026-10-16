const { Markup } = require('telegraf');
const { getTutorials } = require('../api');
const { getBackToMenuKeyboard, getTutorialKeyboard } = require('../keyboards');

/**
 * Handle Tutorial menu - list published posts with 'tutorial' category only.
 */
async function handleTutorial(ctx) {
  try {
    const data = await getTutorials();
    const posts = data.posts || [];

    if (posts.length === 0) {
      const text = `📚 *TUTORIALS & GUIDES*\n━━━━━━━━━━━━━━━━━━━━\nNo tutorial articles published yet. Check back soon!`;
      const keyboard = Markup.inlineKeyboard([
        [Markup.button.url('💬 Help', 'https://t.me/xingzhengx')],
        [Markup.button.callback('🏠 Main Menu', 'main_menu')],
      ]);

      if (ctx.callbackQuery) {
        try {
          return await ctx.editMessageText(text, { parse_mode: 'Markdown', ...keyboard });
        } catch (e) {}
      }
      return ctx.reply(text, { parse_mode: 'Markdown', ...keyboard });
    }

    let text = `📚 *XUNDEFINED TUTORIALS & GUIDES*\n`;
    text += `━━━━━━━━━━━━━━━━━━━━\n\n`;

    posts.forEach((p, idx) => {
      const cleanTitle = String(p.title || '').replace(/[\[\]]/g, '');
      text += `📖 *${idx + 1}.* [[TUTORIAL] ${cleanTitle}](${p.url})\n`;
      if (p.short_description) {
        text += `${p.short_description}\n`;
      }
      text += `\n`;
    });

    const keyboard = getTutorialKeyboard();

    if (ctx.callbackQuery) {
      try {
        return await ctx.editMessageText(text, {
          parse_mode: 'Markdown',
          disable_web_page_preview: true,
          ...keyboard,
        });
      } catch (e) {}
    }

    await ctx.reply(text, {
      parse_mode: 'Markdown',
      disable_web_page_preview: true,
      ...keyboard,
    });
  } catch (err) {
    console.error('handleTutorial error:', err);
    await ctx.reply(`❌ Failed to retrieve tutorials: ${err.message}`, {
      ...getBackToMenuKeyboard(),
    });
  }
}

module.exports = {
  handleTutorial,
};
