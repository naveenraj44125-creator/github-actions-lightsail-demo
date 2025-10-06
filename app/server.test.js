const request = require('supertest');
const app = require('./server');

describe('Lightsail Demo App', () => {
  afterAll(() => {
    // Close the server after tests
    if (app && app.close) {
      app.close();
    }
  });

  describe('GET /', () => {
    it('should return welcome message', async () => {
      const response = await request(app).get('/');
      
      expect(response.status).toBe(200);
      expect(response.body).toHaveProperty('message');
      expect(response.body.message).toBe('Welcome to Lightsail Demo App!');
      expect(response.body).toHaveProperty('version');
      expect(response.body).toHaveProperty('timestamp');
    });
  });

  describe('GET /health', () => {
    it('should return health status', async () => {
      const response = await request(app).get('/health');
      
      expect(response.status).toBe(200);
      expect(response.body).toHaveProperty('status', 'healthy');
      expect(response.body).toHaveProperty('uptime');
      expect(response.body).toHaveProperty('timestamp');
      expect(response.body).toHaveProperty('memory');
      expect(response.body).toHaveProperty('pid');
    });
  });

  describe('GET /api/info', () => {
    it('should return application information', async () => {
      const response = await request(app).get('/api/info');
      
      expect(response.status).toBe(200);
      expect(response.body).toHaveProperty('app', 'Lightsail Demo');
      expect(response.body).toHaveProperty('description');
      expect(response.body).toHaveProperty('features');
      expect(response.body).toHaveProperty('endpoints');
      expect(Array.isArray(response.body.features)).toBe(true);
    });
  });

  describe('GET /api/deploy-info', () => {
    it('should return deployment information', async () => {
      const response = await request(app).get('/api/deploy-info');
      
      expect(response.status).toBe(200);
      expect(response.body).toHaveProperty('deployedAt');
      expect(response.body).toHaveProperty('gitCommit');
      expect(response.body).toHaveProperty('gitBranch');
      expect(response.body).toHaveProperty('workflow');
    });
  });

  describe('GET /nonexistent', () => {
    it('should return 404 for non-existent routes', async () => {
      const response = await request(app).get('/nonexistent');
      
      expect(response.status).toBe(404);
      expect(response.body).toHaveProperty('error', 'Not Found');
      expect(response.body).toHaveProperty('availableEndpoints');
    });
  });
});
