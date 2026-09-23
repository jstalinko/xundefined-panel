const { Markup } = require('telegraf');
const { getDomains, addDomain, deleteDomain } = require('../api');
const { getDomainsKeyboard, getBackToMenuKeyboard } = require('../keyboards');

/**
 * Handle domains list menu
 */
async function handleDomains(ctx) {
  try {
    const data = await getDomains(ctx.from.id);
    const domains = data.domains || [];
    const quotas = data.quotas || [];
    const accountKey = data.account_key || (ctx.state && ctx.state.user ? ctx.state.user.account_key : null) || 'N/A';

    let text = `🌐 *Manage Domains*\n`;
    text += `━━━━━━━━━━━━━━━━━━━━\n`;

    if (quotas.length > 0) {
      quotas.forEach((q) => {
        text += `• *${q.display}*\n`;
      });
      text += `\n`;
    }

    text += `💡 *Instructions:*\n`;
    text += `Domains are registered automatically when you upload your script to your website and input your valid \`account_key\` during setup:\n`;
    text += `🔑 \`${accountKey}\`\n\n`;

    if (domains.length === 0) {
      text += `*Your Authorized Domains:*\n`;
      text += `_No domains registered yet._\n`;
    } else {
      text += `*Your Authorized Domains:*\n`;
      domains.forEach((d, idx) => {
        const statusIcon = d.status === 'active' ? '🟢' : '🟡';
        text += `*${idx + 1}.* \`${d.domain}\` ${statusIcon} (${d.status})\n`;
        text += `   • *Script:* ${d.product_name}\n`;
        text += `   • *Added:* ${d.created_at}\n\n`;
      });
    }

    const keyboard = getDomainsKeyboard(domains);

    if (ctx.callbackQuery) {
      try {
        await ctx.editMessageText(text, {
          parse_mode: 'Markdown',
          ...keyboard,
        });
        return;
      } catch (e) {
        // Fallback to reply if edit fails
      }
    }

    await ctx.reply(text, {
      parse_mode: 'Markdown',
      ...keyboard,
    });
  } catch (err) {
    console.error('handleDomains error:', err);
    await ctx.reply(`❌ Failed to retrieve domains: ${err.message}`, {
      ...getBackToMenuKeyboard()
    });
  }
}

/**
 * Handle prompt to add domain
 */
async function handleAddDomainPrompt(ctx) {
  const text = `🌐 *Register a New Domain*\n━━━━━━━━━━━━━━━━━━━━\nPlease reply with your domain name using the command:\n\n\`/adddomain example.com\`\n\n_Example:_\n\`/adddomain myshop.com\`\n\`/adddomain portal.company.org\``;
  await ctx.reply(text, {
    parse_mode: 'Markdown',
    ...Markup.inlineKeyboard([
      [Markup.button.callback('« Back to Domains', 'menu_domains')],
      [Markup.button.callback('🏠 Main Menu', 'main_menu')],
    ]),
  });
}

/**
 * Handle domain registration command: /adddomain <domain>
 */
async function handleAddDomainCommand(ctx) {
  const text = ctx.message.text || '';
  const parts = text.trim().split(/\s+/);
  
  if (parts.length < 2) {
    return ctx.reply('⚠️ Please provide the domain name.\nExample: `/adddomain mysite.com`', {
      parse_mode: 'Markdown',
    });
  }

  const domain = parts[1];

  try {
    const result = await addDomain(ctx.from.id, domain);
    await ctx.reply(`✅ *Domain Activated!*\n\nDomain \`${result.domain.domain}\` has been successfully bound to your account and script licenses.`, {
      parse_mode: 'Markdown',
      ...Markup.inlineKeyboard([
        [Markup.button.callback('🌐 View Domains', 'menu_domains')],
        [Markup.button.callback('🏠 Main Menu', 'main_menu')],
      ]),
    });
  } catch (err) {
    console.error('handleAddDomainCommand error:', err);
    const msg = err.data && err.data.message ? err.data.message : err.message;
    await ctx.reply(`❌ Registration Failed: ${msg}`, {
      ...Markup.inlineKeyboard([
        [Markup.button.callback('🌐 Back to Domains', 'menu_domains')]
      ])
    });
  }
}

/**
 * Handle delete domain callback
 */
async function handleDeleteDomain(ctx, domainId) {
  try {
    const result = await deleteDomain(ctx.from.id, domainId);
    await ctx.answerCbQuery(result.message || 'Domain removed');
    await handleDomains(ctx);
  } catch (err) {
    console.error('handleDeleteDomain error:', err);
    const msg = err.data && err.data.message ? err.data.message : err.message;
    await ctx.reply(`❌ Delete failed: ${msg}`);
  }
}

module.exports = {
  handleDomains,
  handleAddDomainPrompt,
  handleAddDomainCommand,
  handleDeleteDomain,
};
