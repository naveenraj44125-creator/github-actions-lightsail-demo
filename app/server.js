const express = require('express');
const cors = require('cors');
const helmet = require('helmet');
const morgan = require('morgan');

const app = express();
const PORT = process.env.PORT || 3000;

// Middleware
app.use(helmet());
app.use(cors());
app.use(morgan('combined'));
app.use(express.json());
app.use(express.static('public'));

// Routes
app.get('/', (req, res) => {
  res.json({
    message: 'Welcome to Lightsail Demo App!',
    version: '1.0.0',
    timestamp: new Date().toISOString(),
    environment: process.env.NODE_ENV || 'development'
  });
});

app.get('/health', (req, res) => {
  res.status(200).json({
    status: 'healthy',
    uptime: process.uptime(),
    timestamp: new Date().toISOString(),
    memory: process.memoryUsage(),
    pid: process.pid
  });
});

app.get('/api/info', (req, res) => {
  res.json({
    app: 'Lightsail Demo',
    description: 'Demo application deployed via GitHub Actions to AWS Lightsail',
    features: [
      'Automated CI/CD with GitHub Actions',
      'AWS Lightsail deployment',
      'Health check endpoints',
      'Express.js REST API',
      'Security middleware'
    ],
    endpoints: {
      '/': 'Welcome message',
      '/health': 'Health check',
      '/api/info': 'Application information',
      '/api/deploy-info': 'Deployment information'
    }
  });
});

app.get('/api/deploy-info', (req, res) => {
  res.json({
    deployedAt: process.env.DEPLOY_TIME || 'Unknown',
    gitCommit: process.env.GITHUB_SHA || 'Unknown',
    gitBranch: process.env.GITHUB_REF_NAME || 'Unknown',
    workflow: process.env.GITHUB_WORKFLOW || 'Unknown',
    runId: process.env.GITHUB_RUN_ID || 'Unknown',
    actor: process.env.GITHUB_ACTOR || 'Unknown'
  });
});

// Error handling middleware
app.use((err, req, res, next) => {
  console.error('Error:', err);
  res.status(500).json({
    error: 'Internal Server Error',
    message: process.env.NODE_ENV === 'development' ? err.message : 'Something went wrong'
  });
});

// 404 handler
app.use((req, res) => {
  res.status(404).json({
    error: 'Not Found',
    message: `Route ${req.method} ${req.path} not found`,
    availableEndpoints: ['/', '/health', '/api/info', '/api/deploy-info']
  });
});

// Graceful shutdown
process.on('SIGTERM', () => {
  console.log('SIGTERM received, shutting down gracefully');
  server.close(() => {
    console.log('Process terminated');
  });
});

process.on('SIGINT', () => {
  console.log('SIGINT received, shutting down gracefully');
  server.close(() => {
    console.log('Process terminated');
  });
});

const server = app.listen(PORT, () => {
  console.log(`Server running on port ${PORT}`);
  console.log(`Environment: ${process.env.NODE_ENV || 'development'}`);
  console.log(`Health check: http://localhost:${PORT}/health`);
});

module.exports = app;
