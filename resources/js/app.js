import './bootstrap';

const apiPath = (path) => (path.startsWith('/') ? path : `/${path}`);

window.expenseTrackerConfig = {
    ...(window.expenseTrackerConfig ?? {}),
    apiBaseUrl: window.expenseTrackerConfig?.apiBaseUrl ?? '/api',
};

window.expenseTrackerRoutes = Object.freeze({
    home: '/',
    dashboard: '/dashboard',
    accounts: '/accounts',
    categories: '/categories',
    transactions: '/transactions',
    budgets: '/budgets',
});

window.expenseTrackerApiRoutes = Object.freeze({
    login: apiPath('/login'),
    dashboard: (userId) => apiPath(`/users/${userId}/dashboard`),
    categories: (userId) => apiPath(`/users/${userId}/categories`),
    category: (userId, categoryId) => apiPath(`/users/${userId}/categories/${categoryId}`),
    accounts: (userId) => apiPath(`/users/${userId}/accounts`),
    account: (userId, accountId) => apiPath(`/users/${userId}/accounts/${accountId}`),
    transactions: (userId) => apiPath(`/users/${userId}/transactions`),
    transaction: (userId, transactionId) => apiPath(`/users/${userId}/transactions/${transactionId}`),
    budgets: (userId) => apiPath(`/users/${userId}/budgets`),
    budget: (userId, budgetId) => apiPath(`/users/${userId}/budgets/${budgetId}`),
});
