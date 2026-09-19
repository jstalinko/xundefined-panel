const { initUser } = require('../api');

/**
 * Middleware to auto-register or fetch user on every interaction
 */
async function autoRegisterMiddleware(ctx, next) {
  if (!ctx.from) {
    return next();
  }

  try {
    const initData = await initUser(ctx.from);
    if (initData && initData.user) {
      ctx.state.user = initData.user;
      ctx.state.isNewUser = !!initData.is_new;
      ctx.state.initMessage = initData.message;
    }
  } catch (error) {
    console.error('AutoRegisterMiddleware error:', error.message);
    // Even if API fails temporarily, continue so bot doesn't crash completely
    ctx.state.apiError = error.message;
  }

  return next();
}

module.exports = {
  autoRegisterMiddleware,
};
