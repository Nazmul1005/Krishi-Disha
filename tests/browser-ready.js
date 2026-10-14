module.exports = async () => {
  for (let attempt = 0; attempt < 40; attempt++) {
    try {
      const response = await fetch('http://127.0.0.1:8082/KrishiDisha/health.php', { signal: AbortSignal.timeout(2000) });
      if (response.ok && (await response.json()).status === 'ok') return;
    } catch {}
    await new Promise(resolve => setTimeout(resolve, 500));
  }
  throw new Error('The isolated browser service is not ready on port 8082. Prepare browser fixtures and start the test service.');
};
