const path = require('path');
const fs = require('fs');
const { Markup } = require('telegraf');
const { getFreeTools, getFreeTool, downloadFreeToolFile } = require('../api');
const { getBackToMenuKeyboard } = require('../keyboards');

/**
 * Resolve local file path for a free tool if stored locally
 */
function resolveFreeToolFilePath(filePath) {
  if (!filePath) return null;
  const cleanRel = String(filePath).replace(/^\/+/, '');
  const candidatePaths = [
    path.resolve(__dirname, '../../../storage/app/private', cleanRel),
    path.resolve(__dirname, '../../storage/app/private', cleanRel),
    path.resolve(__dirname, '../../../storage/app/private/free-tools', path.basename(cleanRel)),
    path.resolve(__dirname, '../../storage/app/private/free-tools', path.basename(cleanRel)),
  ];

  for (const fullPath of candidatePaths) {
    if (fs.existsSync(fullPath) && fs.statSync(fullPath).isFile()) {
      return fullPath;
    }
  }
  return null;
}

/**
 * Handle listing all active Free Tools
 */
async function handleFreeTools(ctx) {
  try {
    const data = await getFreeTools();
    const tools = data.free_tools || [];

    if (tools.length === 0) {
      const text = `🛠️ *FREE TOOLS*\n━━━━━━━━━━━━━━━━━━━━\nNo free tools available right now. Check back soon!`;
      const keyboard = getBackToMenuKeyboard();
      if (ctx.callbackQuery) {
        try {
          return await ctx.editMessageText(text, { parse_mode: 'Markdown', ...keyboard });
        } catch (e) {}
      }
      return ctx.reply(text, { parse_mode: 'Markdown', ...keyboard });
    }

    let text = `🛠️ *FREE TOOLS*\n`;
    text += `━━━━━━━━━━━━━━━━━━━━\n`;
    text += `Select a free tool below to download or open:`;

    const inlineButtons = tools.map((tool) => {
      const icon = tool.type === 'file' ? '📦' : '🌐';
      const typeLabel = tool.type ? tool.type.toUpperCase() : 'TOOL';
      return [
        Markup.button.callback(`${icon} ${tool.name} [${typeLabel}]`, `ft_view_${tool.id}`)
      ];
    });

    inlineButtons.push([Markup.button.callback('🏠 Main Menu', 'main_menu')]);

    const keyboard = Markup.inlineKeyboard(inlineButtons);

    if (ctx.callbackQuery) {
      try {
        return await ctx.editMessageText(text, {
          parse_mode: 'Markdown',
          ...keyboard,
        });
      } catch (e) {}
    }

    await ctx.reply(text, {
      parse_mode: 'Markdown',
      ...keyboard,
    });
  } catch (err) {
    console.error('handleFreeTools error:', err);
    await ctx.reply(`❌ Failed to load free tools: ${err.message}`, {
      ...getBackToMenuKeyboard(),
    });
  }
}

/**
 * Handle viewing a single Free Tool detail
 */
async function handleFreeToolDetail(ctx, toolId) {
  try {
    const data = await getFreeTool(toolId);
    const tool = data.free_tool;

    if (!tool) {
      return ctx.reply('❌ Free tool not found.', { ...getBackToMenuKeyboard() });
    }

    const isFile = tool.type === 'file';
    let text = `🛠️ *${tool.name}*\n`;
    text += `━━━━━━━━━━━━━━━━━━━━\n`;
    text += `• *Type:* \`${isFile ? 'FILE ARCHIVE' : 'EXTERNAL URL'}\`\n`;

    if (isFile) {
      if (tool.file_name) {
        text += `• *File:* \`${tool.file_name}\`\n`;
      }
      if (tool.file_size_human) {
        text += `• *Size:* \`${tool.file_size_human}\`\n`;
      }
    } else if (tool.url) {
      text += `• *URL:* ${tool.url}\n`;
    }

    if (tool.description) {
      const cleanDesc = String(tool.description).replace(/<[^>]*>?/gm, '').trim();
      text += `\n📄 *Description:*\n${cleanDesc}\n`;
    }

    text += `━━━━━━━━━━━━━━━━━━━━`;

    const buttons = [];
    if (isFile) {
      buttons.push([
        Markup.button.callback(`📥 Download File (${tool.file_name || 'Download'})`, `ft_dl_${tool.id}`)
      ]);
    } else if (tool.url) {
      buttons.push([
        Markup.button.url('🌐 Open Tool URL', tool.url)
      ]);
    }

    buttons.push([
      Markup.button.callback('« Back to Free Tools', 'menu_freetools'),
      Markup.button.callback('🏠 Main Menu', 'main_menu'),
    ]);

    const keyboard = Markup.inlineKeyboard(buttons);

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
    console.error('handleFreeToolDetail error:', err);
    await ctx.reply(`❌ Failed to load free tool: ${err.message}`, {
      ...getBackToMenuKeyboard(),
    });
  }
}

/**
 * Handle downloading and sending a file-based Free Tool to the user
 */
async function handleDownloadFreeToolFile(ctx, toolId) {
  try {
    const data = await getFreeTool(toolId);
    const tool = data.free_tool;

    if (!tool || tool.type !== 'file') {
      return ctx.reply('❌ File not available for this tool.', { ...getBackToMenuKeyboard() });
    }

    const filename = tool.file_name || `${tool.slug || 'free-tool'}.txt`;
    const caption = `🛠️ *FREE TOOL DOWNLOAD*\n` +
      `━━━━━━━━━━━━━━━━━━━━\n` +
      `• *Tool Name:* ${tool.name}\n` +
      `• *Filename:* \`${filename}\`\n` +
      (tool.file_size_human ? `• *Size:* \`${tool.file_size_human}\`\n` : '') +
      `━━━━━━━━━━━━━━━━━━━━`;

    const navKeyboard = Markup.inlineKeyboard([
      [Markup.button.callback('« Back to Free Tools', 'menu_freetools')],
      [Markup.button.callback('🏠 Main Menu', 'main_menu')],
    ]);

    const localPath = resolveFreeToolFilePath(tool.file_path || filename);

    if (typeof ctx.replyWithDocument === 'function') {
      try {
        if (localPath && fs.existsSync(localPath)) {
          await ctx.replyWithDocument(
            { source: fs.createReadStream(localPath), filename },
            {
              caption,
              parse_mode: 'Markdown',
              ...navKeyboard,
            }
          );
          return;
        }

        const buffer = await downloadFreeToolFile(tool.id);
        await ctx.replyWithDocument(
          { source: buffer, filename },
          {
            caption,
            parse_mode: 'Markdown',
            ...navKeyboard,
          }
        );
        return;
      } catch (sendErr) {
        console.warn('replyWithDocument failed for free tool:', sendErr.message);
      }
    }

    const fallbackText = `${caption}\n📎 *File:* \`${filename}\``;
    if (ctx.callbackQuery) {
      await ctx.editMessageText(fallbackText, {
        parse_mode: 'Markdown',
        ...navKeyboard,
      });
    } else {
      await ctx.reply(fallbackText, {
        parse_mode: 'Markdown',
        ...navKeyboard,
      });
    }
  } catch (err) {
    console.error('handleDownloadFreeToolFile error:', err);
    await ctx.reply(`❌ Failed to download free tool file: ${err.message}`, {
      ...getBackToMenuKeyboard(),
    });
  }
}

module.exports = {
  handleFreeTools,
  handleFreeToolDetail,
  handleDownloadFreeToolFile,
};
