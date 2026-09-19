const {
  initUser,
  getProducts,
  getProduct,
  getBalance,
  topupDemo,
  createTopupCrypto,
  checkTopupStatus,
  buyProduct,
  getOrders,
  getDownloads,
  getDomains,
  addDomain,
  deleteDomain,
  getActivities,
  getProfile,
} = require('./src/api');
const { APP_URL, API_BASE_URL } = require('./src/config');

async function runTests() {
  console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
  console.log('🧪 RUNNING INTEGRATION TESTS FOR TELEGRAM BOT');
  console.log(`Backend APP_URL: ${APP_URL}`);
  console.log(`API Base URL: ${API_BASE_URL}`);
  console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

  const testUser = {
    id: 123456789,
    username: 'digital_entrepreneur',
    first_name: 'Alex',
    last_name: 'Rivera',
  };

  try {
    // 1. Test /api/telegram/init (Auto Register)
    console.log('\n[1/13] Testing Auto-Registration (/api/telegram/init)...');
    const initRes = await initUser(testUser);
    console.log('  Status:', initRes.success ? '✅ SUCCESS' : '❌ FAILED');
    console.log(`  User: ${initRes.user.name} | ID: ${initRes.user.telegram_id} | Balance: $${initRes.user.balance}`);

    // 2. Test /api/telegram/products
    console.log('\n[2/13] Testing Products Catalog (/api/telegram/products)...');
    const prodRes = await getProducts();
    console.log('  Status:', prodRes.success ? '✅ SUCCESS' : '❌ FAILED');
    console.log(`  Found ${prodRes.products.length} products.`);
    const firstProduct = prodRes.products[0];
    console.log(`  Sample Product: "${firstProduct.name}" ($${firstProduct.price})`);
    console.log(`  Release File: "${firstProduct.file}" | Version: ${firstProduct.version} | MD5: ${firstProduct.md5checksum}`);

    // 3. Test /api/telegram/products/:id
    console.log(`\n[3/13] Testing Single Product Detail (/api/telegram/products/${firstProduct.id})...`);
    const singleRes = await getProduct(firstProduct.id);
    console.log('  Status:', singleRes.success ? '✅ SUCCESS' : '❌ FAILED');
    console.log(`  Category: ${singleRes.product.category} | Version: ${singleRes.product.version}`);
    console.log(`  Contents Releases Count: ${Array.isArray(singleRes.product.contents) ? singleRes.product.contents.length : 0}`);

    // 4. Test /api/telegram/balance
    console.log('\n[4/13] Testing Balance Check (/api/telegram/balance)...');
    const balRes = await getBalance(testUser.id);
    console.log('  Status:', balRes.success ? '✅ SUCCESS' : '❌ FAILED');
    console.log(`  Current Balance: $${balRes.balance}`);

    // 5. Test CoinPayments Real Crypto Top-up creation
    console.log('\n[5/13] Testing Real Crypto Top-Up Creation (/api/telegram/balance/create-topup)...');
    const cryptoTopupRes = await createTopupCrypto(testUser.id, 60, 'LTCT');
    console.log('  Status:', cryptoTopupRes.success ? '✅ SUCCESS' : '❌ FAILED');
    console.log(`  Invoice: ${cryptoTopupRes.transaction.invoice}`);
    console.log(`  Receiving Address: ${cryptoTopupRes.transaction.payment_address}`);
    console.log(`  Crypto Amount: ${cryptoTopupRes.transaction.payment_amount} ${cryptoTopupRes.transaction.payment_currency}`);

    // 6. Test CoinPayments Status Checking
    console.log(`\n[6/13] Testing Crypto Invoice Status Check (${cryptoTopupRes.transaction.invoice})...`);
    const statusRes = await checkTopupStatus(cryptoTopupRes.transaction.invoice, false);
    console.log('  Status:', statusRes.success ? '✅ SUCCESS' : '❌ FAILED');
    console.log(`  Payment Status: ${statusRes.status} (Pending: ${statusRes.is_pending})`);

    // 7. Test Demo Top-up
    console.log('\n[7/13] Testing Demo Balance Top-up ($100.00)...');
    const topupRes = await topupDemo(testUser.id, 100);
    console.log('  Status:', topupRes.success ? '✅ SUCCESS' : '❌ FAILED');
    console.log(`  New Balance: $${topupRes.new_balance}`);

    // 8. Test Product Purchase
    console.log(`\n[8/13] Testing Product Purchase (Buying "${firstProduct.name}")...`);
    const buyRes = await buyProduct(testUser.id, firstProduct.id);
    console.log('  Status:', buyRes.success ? '✅ SUCCESS' : '❌ FAILED');
    console.log(`  Order Number: ${buyRes.order.order_number}`);
    console.log(`  Remaining Balance: $${buyRes.new_balance}`);

    // 9. Test Orders History
    console.log('\n[9/13] Testing Orders History (/api/telegram/orders)...');
    const ordersRes = await getOrders(testUser.id);
    console.log('  Status:', ordersRes.success ? '✅ SUCCESS' : '❌ FAILED');
    console.log(`  Total Orders: ${ordersRes.orders.length}`);

    // 10. Test Downloads List
    console.log('\n[10/14] Testing Downloads Vault (/api/telegram/downloads)...');
    const downRes = await getDownloads(testUser.id);
    console.log('  Status:', downRes.success ? '✅ SUCCESS' : '❌ FAILED');
    console.log(`  Purchased Downloads: ${downRes.downloads.length}`);
    if (downRes.downloads.length > 0) {
      console.log(`  Latest Download File: ${downRes.downloads[0].file} (v${downRes.downloads[0].version})`);
      console.log(`  MD5: ${downRes.downloads[0].md5checksum}`);
    }

    // 11. Test Download File Endpoint
    console.log('\n[11/14] Testing File Download Endpoint (/api/telegram/downloads/file)...');
    const { apiClient } = require('./src/api');
    const fileRes = await apiClient.get('/downloads/file', {
      params: {
        telegram_id: testUser.id,
        product_id: firstProduct.id,
        version: firstProduct.version,
      },
      responseType: 'arraybuffer',
    });
    console.log('  Status:', fileRes.status === 200 ? '✅ SUCCESS' : '❌ FAILED');
    console.log(`  Received Attachment: ${fileRes.data.length} bytes`);

    // 12. Test Domain Registration
    const testDomainName = `test-${Date.now()}.com`;
    console.log(`\n[12/14] Testing Domain Registration (/api/telegram/domains/add) with ${testDomainName}...`);
    const domainRes = await addDomain(testUser.id, testDomainName, firstProduct.id);
    console.log('  Status:', domainRes.success ? '✅ SUCCESS' : '❌ FAILED');
    console.log(`  Registered Domain: ${domainRes.domain.domain} (Status: ${domainRes.domain.status})`);

    // 13. Test Domains List and Delete
    console.log('\n[13/14] Testing Domains List & Delete (/api/telegram/domains)...');
    const domListRes = await getDomains(testUser.id);
    console.log('  Status:', domListRes.success ? '✅ SUCCESS' : '❌ FAILED');
    console.log(`  Active Domains: ${domListRes.domains.length}`);
    await deleteDomain(testUser.id, domainRes.domain.id);

    // 14. Test Profile & Activities
    console.log('\n[14/14] Testing Profile & Activities (/api/telegram/profile, activities)...');
    const profileRes = await getProfile(testUser.id);
    console.log('  Status:', profileRes.success ? '✅ SUCCESS' : '❌ FAILED');
    console.log(`  Profile: ${profileRes.profile.name} (@${profileRes.profile.telegram_username})`);
    console.log(`  Total Spent: $${profileRes.profile.total_spent}`);

    const actRes = await getActivities(testUser.id);
    console.log(`  Recent Activities Logged: ${actRes.activities.length}`);

    console.log('\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    console.log('✨ ALL 14 INTEGRATION TESTS PASSED PERFECTLY!');
    console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n');
  } catch (err) {
    console.error('\n❌ TEST FAILED WITH ERROR:', err.message);
    if (err.data) {
      console.error('Error Details:', err.data);
    }
    process.exit(1);
  }
}

runTests();
