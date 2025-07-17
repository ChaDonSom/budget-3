const fs = require('fs');
const path = require('path');

function walk(dir, callback) {
  fs.readdirSync(dir).forEach((f) => {
    const dirPath = path.join(dir, f);
    const isDirectory = fs.statSync(dirPath).isDirectory();
    isDirectory ? walk(dirPath, callback) : callback(path.join(dir, f));
  });
}

const oversized = [];
walk('src', (filePath) => {
  if (filePath.endsWith('.vue')) {
    const lines = fs.readFileSync(filePath, 'utf8').split('\n').length;
    if (lines > 150) {
      oversized.push({ file: filePath, lines });
    }
  }
});

if (oversized.length) {
  console.log('Oversized Vue components (>150 lines):');
  oversized.forEach(({ file, lines }) => {
    console.log(`${file}: ${lines} lines`);
  });
  process.exit(1);
} else {
  console.log('All Vue components are within the 150 line limit.');
}
