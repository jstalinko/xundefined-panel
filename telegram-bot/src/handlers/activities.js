const { Markup } = require('telegraf');
const { getActivities, downloadActivitiesTxt } = require('../api');
const { getBackToMenuKeyboard, getActivitiesKeyboard } = require('../keyboards');

/**
 * Handle activities menu - displays the 10 most recent activity logs.
 */
async function handleActivities(ctx) {
  try {
    const data = await getActivities(ctx.from.id, false);
    const activities = (data.activities || []).slice(0, 10);

    if (activities.length === 0) {
      const text = `📊 *Account Activities*\n━━━━━━━━━━━━━━━━━━━━\nNo recent activity found for your account.`;
      const keyboard = getActivitiesKeyboard(false);

      if (ctx.callbackQuery) {
        try {
          return await ctx.editMessageText(text, {
            parse_mode: 'Markdown',
            ...keyboard,
          });
        } catch (e) {
          // Fallback to reply
        }
      }

      return ctx.reply(text, {
        parse_mode: 'Markdown',
        ...keyboard,
      });
    }

    let text = `📊 *Recent Account Activities (Last 10)*\n`;
    text += `━━━━━━━━━━━━━━━━━━━━\n\n`;

    activities.forEach((act) => {
      let icon = '🔹';
      if (act.action === 'REGISTER') icon = '🎉';
      if (act.action === 'PURCHASE') icon = '🛒';
      if (act.action === 'BALANCE_TOPUP') icon = '💰';
      if (act.action === 'BIND_DOMAIN') icon = '🌐';
      if (act.action === 'REMOVE_DOMAIN') icon = '🗑️';

      text += `${icon} *[${act.action}]* - ${act.date}\n`;
      text += `   ${act.description}\n\n`;
    });

    const keyboard = getActivitiesKeyboard(true);

    if (ctx.callbackQuery) {
      try {
        return await ctx.editMessageText(text, {
          parse_mode: 'Markdown',
          ...keyboard,
        });
      } catch (e) {
        // Fallback to reply
      }
    }

    await ctx.reply(text, {
      parse_mode: 'Markdown',
      ...keyboard,
    });
  } catch (err) {
    console.error('handleActivities error:', err);
    await ctx.reply(`❌ Failed to retrieve activities: ${err.message}`, {
      ...getBackToMenuKeyboard(),
    });
  }
}

/**
 * Handle downloading all activities as a TXT data file.
 */
async function handleDownloadActivitiesTxt(ctx) {
  try {
    await ctx.answerCbQuery('Exporting activity logs...');
    const telegramId = ctx.from.id;

    let fileBuffer;
    try {
      fileBuffer = await downloadActivitiesTxt(telegramId);
    } catch (apiErr) {
      console.warn('Backend download endpoint error, generating fallback buffer:', apiErr.message);
      // Fallback: fetch all activities and format into text
      const data = await getActivities(telegramId, true);
      const allActivities = data.activities || [];

      let content = "====================================================\n";
      content += "       XUNDEFINED - ACCOUNT ACTIVITY LOGS\n";
      content += "====================================================\n";
      content += `Telegram ID: ${telegramId}\n`;
      content += `Generated: ${new Date().toISOString()}\n`;
      content += `Total Records: ${allActivities.length}\n`;
      content += "====================================================\n\n";

      allActivities.forEach((act, idx) => {
        content += `[#${idx + 1}] [${act.action}] ${act.date}\n`;
        content += `Details: ${act.description}\n`;
        content += "----------------------------------------------------\n";
      });

      fileBuffer = Buffer.from(content, 'utf-8');
    }

    const filename = `activities-${telegramId}.txt`;
    const buffer = Buffer.isBuffer(fileBuffer) ? fileBuffer : Buffer.from(fileBuffer);

    if (typeof ctx.replyWithDocument === 'function') {
      try {
        await ctx.replyWithDocument(
          { source: buffer, filename: filename },
          {
            caption: `📄 *Account Activities Log*\nExported complete activity logs as TXT data.`,
            parse_mode: 'Markdown',
          }
        );
        return;
      } catch (docErr) {
        console.warn('ctx.replyWithDocument failed:', docErr.message);
      }
    }

    // Fallback if document cannot be sent directly
    await ctx.reply(`📄 *Account Activities Log*\n\n\`\`\`\n${buffer.toString('utf-8').slice(0, 3500)}\n\`\`\``, {
      parse_mode: 'Markdown',
    });
  } catch (err) {
    console.error('handleDownloadActivitiesTxt error:', err);
    await ctx.reply(`❌ Failed to download activities: ${err.message}`);
  }
}

module.exports = {
  handleActivities,
  handleDownloadActivitiesTxt,
};
