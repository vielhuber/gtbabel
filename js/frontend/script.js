import DetectChanges from './DetectChanges';

const d = new DetectChanges();

const ready = new Promise(resolve => {
    if (document.readyState !== 'loading') {
        return resolve();
    } else {
        document.addEventListener('DOMContentLoaded', () => {
            return resolve();
        });
    }
});

ready.then(() => {
    d.init();
});
