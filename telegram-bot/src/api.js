const axios = require('axios');
const { API_BASE_URL } = require('./config');

const apiClient = axios.create({
  baseURL: API_BASE_URL,
  timeout: 10000,
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  },
});

/**
 * Handle API errors gracefully
 */
function handleApiError(error, customMsg) {
  if (error.response) {
    const data = error.response.data;
    const msg = data.message || error.response.statusText;
    const err = new Error(msg);
    err.status = error.response.status;
    err.data = data;
    return err;
  }
  return new Error(customMsg || error.message || 'Cannot reach API backend');
}

/**
 * Auto-register or fetch user from Laravel backend
 */
async function initUser(telegramUser) {
  try {
    const payload = {
      telegram_id: String(telegramUser.id),
      telegram_username: telegramUser.username || null,
      first_name: telegramUser.first_name || '',
      last_name: telegramUser.last_name || '',
    };
    const response = await apiClient.post('/init', payload);
    return response.data;
  } catch (error) {
    throw handleApiError(error, 'Failed to initialize user on backend');
  }
}

/**
 * Get all available digital website script products
 */
async function getProducts() {
  try {
    const response = await apiClient.get('/products');
    return response.data;
  } catch (error) {
    throw handleApiError(error, 'Failed to fetch products');
  }
}

/**
 * Get product detail by ID or slug
 */
async function getProduct(id) {
  try {
    const response = await apiClient.get(`/products/${id}`);
    return response.data;
  } catch (error) {
    throw handleApiError(error, 'Failed to fetch product details');
  }
}

/**
 * Purchase product using balance
 */
async function buyProduct(telegramId, productId) {
  try {
    const response = await apiClient.post('/orders/buy', {
      telegram_id: String(telegramId),
      product_id: Number(productId),
    });
    return response.data;
  } catch (error) {
    throw handleApiError(error, 'Purchase failed');
  }
}

/**
 * Get user's purchased downloads
 */
async function getDownloads(telegramId) {
  try {
    const response = await apiClient.get('/downloads', {
      params: { telegram_id: String(telegramId) },
    });
    return response.data;
  } catch (error) {
    throw handleApiError(error, 'Failed to fetch downloads');
  }
}

/**
 * Get user's orders history
 */
async function getOrders(telegramId) {
  try {
    const response = await apiClient.get('/orders', {
      params: { telegram_id: String(telegramId) },
    });
    return response.data;
  } catch (error) {
    throw handleApiError(error, 'Failed to fetch orders');
  }
}

/**
 * Get user's recent activities
 */
async function getActivities(telegramId, all = false) {
  try {
    const response = await apiClient.get('/activities', {
      params: { telegram_id: String(telegramId), all: all ? 1 : 0 },
    });
    return response.data;
  } catch (error) {
    throw handleApiError(error, 'Failed to fetch activities');
  }
}

/**
 * Get user's bound domains
 */
async function getDomains(telegramId) {
  try {
    const response = await apiClient.get('/domains', {
      params: { telegram_id: String(telegramId) },
    });
    return response.data;
  } catch (error) {
    throw handleApiError(error, 'Failed to fetch domains');
  }
}

/**
 * Add / register domain license
 */
async function addDomain(telegramId, domain, productId = null) {
  try {
    const response = await apiClient.post('/domains/add', {
      telegram_id: String(telegramId),
      domain: domain,
      product_id: productId,
    });
    return response.data;
  } catch (error) {
    throw handleApiError(error, 'Failed to register domain');
  }
}

/**
 * Delete / unregister domain license
 */
async function deleteDomain(telegramId, domainId) {
  try {
    const response = await apiClient.post('/domains/delete', {
      telegram_id: String(telegramId),
      domain_id: Number(domainId),
    });
    return response.data;
  } catch (error) {
    throw handleApiError(error, 'Failed to remove domain');
  }
}

/**
 * Get user profile details
 */
async function getProfile(telegramId) {
  try {
    const response = await apiClient.get('/profile', {
      params: { telegram_id: String(telegramId) },
    });
    return response.data;
  } catch (error) {
    throw handleApiError(error, 'Failed to fetch profile');
  }
}

/**
 * Get user balance info
 */
async function getBalance(telegramId) {
  try {
    const response = await apiClient.get('/balance', {
      params: { telegram_id: String(telegramId) },
    });
    return response.data;
  } catch (error) {
    throw handleApiError(error, 'Failed to fetch balance');
  }
}

/**
 * Quick top up demo balance
 */
async function topupDemo(telegramId, amount) {
  try {
    const response = await apiClient.post('/balance/topup-demo', {
      telegram_id: String(telegramId),
      amount: Number(amount),
    });
    return response.data;
  } catch (error) {
    throw handleApiError(error, 'Top-up failed');
  }
}

/**
 * Create real CoinPayments cryptocurrency deposit invoice
 */
async function createTopupCrypto(telegramId, amount, currency = 'USDT.TRC20') {
  try {
    const response = await apiClient.post('/balance/create-topup', {
      telegram_id: String(telegramId),
      amount: Number(amount),
      currency2: currency,
    });
    return response.data;
  } catch (error) {
    throw handleApiError(error, 'Failed to create CoinPayments crypto invoice');
  }
}

/**
 * Check real CoinPayments deposit status
 */
async function checkTopupStatus(invoice, refresh = true) {
  try {
    const response = await apiClient.get(`/balance/topup-status/${invoice}`, {
      params: { refresh: refresh ? 1 : 0 },
    });
    return response.data;
  } catch (error) {
    throw handleApiError(error, 'Failed to check payment status');
  }
}

/**
 * Get accepted crypto coins list
 */
async function getCryptoCurrencies() {
  try {
    const response = await apiClient.get('/balance/currencies');
    return response.data;
  } catch (error) {
    throw handleApiError(error, 'Failed to fetch currencies');
  }
}

/**
 * Download all activities as a TXT file buffer
 */
async function downloadActivitiesTxt(telegramId) {
  try {
    const response = await apiClient.get('/activities/download', {
      params: { telegram_id: String(telegramId) },
      responseType: 'arraybuffer',
    });
    return Buffer.from(response.data);
  } catch (error) {
    throw handleApiError(error, 'Failed to download activities text file');
  }
}

/**
 * Get published posts / news from database
 */
async function getPosts() {
  try {
    const response = await apiClient.get('/posts');
    return response.data;
  } catch (error) {
    throw handleApiError(error, 'Failed to fetch posts/news');
  }
}

module.exports = {
  apiClient,
  initUser,
  getProducts,
  getProduct,
  buyProduct,
  getDownloads,
  getOrders,
  getActivities,
  downloadActivitiesTxt,
  getDomains,
  addDomain,
  deleteDomain,
  getProfile,
  getBalance,
  topupDemo,
  createTopupCrypto,
  checkTopupStatus,
  getCryptoCurrencies,
  getPosts,
};
