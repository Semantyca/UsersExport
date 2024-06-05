const fs = require('fs-extra');
const path = require('path');
require('dotenv').config();


const directory = path.resolve(__dirname, process.env.BUILD_OUTPUT_DIR);

fs.emptyDirSync(directory);
console.log(`Cleared: ${directory}`);
