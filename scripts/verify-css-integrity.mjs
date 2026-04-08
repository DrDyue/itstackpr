import fs from 'node:fs/promises';
import path from 'node:path';
import postcss from 'postcss';

const root = process.cwd();
const cssFiles = [
    'resources/css/app.css',
    'resources/css/globals.css',
];

const forbiddenTokens = [
    'accent-cdirektīves',
    'inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-white',
    'min-[max(',
    'shadow-smadow-sm',
    'sky-100sky-100',
];

const readCss = async (relativeFile) => {
    const absoluteFile = path.join(root, relativeFile);
    return {
        relativeFile,
        content: await fs.readFile(absoluteFile, 'utf8'),
    };
};

const run = async () => {
    const files = await Promise.all(cssFiles.map(readCss));
    let hasFailure = false;

    for (const { relativeFile, content } of files) {
        try {
            postcss.parse(content, { from: relativeFile });
        } catch (error) {
            hasFailure = true;
            console.error(`❌ CSS parse error in ${relativeFile}`);
            console.error(error.message);
        }

        for (const token of forbiddenTokens) {
            if (content.includes(token)) {
                hasFailure = true;
                console.error(`❌ Forbidden token "${token}" found in ${relativeFile}`);
            }
        }
    }

    if (hasFailure) {
        process.exitCode = 1;
        return;
    }

    console.log('✅ CSS integrity check passed.');
};

run().catch((error) => {
    console.error('❌ CSS integrity check failed unexpectedly.');
    console.error(error);
    process.exit(1);
});
