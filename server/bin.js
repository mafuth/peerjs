const { PeerServer } = require('peer');

const peerServer = PeerServer({ port: 9000, path: '/connect-peers' });

console.log('Server started');