(function (root) {
  'use strict';
  function calculate(input) {
    const number = (key, min = 0, max = 1e12) => {
      const value = Number(input[key]);
      if (!Number.isFinite(value) || value < min || value > max) throw new Error('Check the numeric inputs.');
      return value;
    };
    const factors = { acre: 1, decimal: 0.01, hectare: 2.471053814671653 };
    if (!(input.unit in factors)) throw new Error('Choose a supported land unit.');
    const acres = number('area', 0.000001) * factors[input.unit];
    const yieldKg = number('yieldKg');
    const price = number('price');
    const cost = number('cost');
    const loss = number('loss', 0, 100) / 100;
    const commission = number('commission', 0, 99.99) / 100;
    const saleable = yieldKg * (1 - loss);
    const scenario = multiplier => {
      const gross = saleable * price * multiplier;
      const fee = gross * commission;
      const profit = gross - fee - cost;
      return { gross, fee, profit, roi: cost > 0 ? profit / cost * 100 : null, perAcre: profit / acres };
    };
    return { acres, saleable, low: scenario(0.8), base: scenario(1), high: scenario(1.2), breakEvenPrice: saleable > 0 ? cost / (saleable * (1 - commission)) : null, breakEvenYield: price > 0 && loss < 1 ? cost / (price * (1 - commission) * (1 - loss)) : null };
  }
  if (typeof module !== 'undefined') module.exports = { calculate };
  else root.KrishiCalculator = { calculate };
})(typeof window === 'undefined' ? globalThis : window);

if (typeof document !== 'undefined') document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('farmEstimate');
  if (!form) return;
  const money = value => value === null ? '—' : '৳ ' + value.toLocaleString('bn-BD', { maximumFractionDigits: 2 });
  form.addEventListener('submit', event => {
    event.preventDefault();
    try {
      const fields = new FormData(form);
      const costs = [...form.querySelectorAll('[data-cost]')].map(field => Number(field.value || 0));
      if (costs.some(value => !Number.isFinite(value) || value < 0)) throw new Error('Costs must be nonnegative.');
      const result = KrishiCalculator.calculate({ area: fields.get('area'), unit: fields.get('unit'), yieldKg: fields.get('yieldKg'), price: fields.get('price'), loss: fields.get('loss'), commission: fields.get('commission'), cost: costs.reduce((a, b) => a + b, 0) });
      document.getElementById('estimateError').textContent = '';
      document.getElementById('estimateResults').hidden = false;
      ['low', 'base', 'high'].forEach(name => {
        document.getElementById(name + 'Gross').textContent = money(result[name].gross);
        document.getElementById(name + 'Profit').textContent = money(result[name].profit);
      });
      document.getElementById('estimateDetails').textContent = 'Saleable yield / বিক্রয়যোগ্য ফলন: ' + result.saleable.toFixed(2) + ' kg · ROI: ' + (result.base.roi === null ? '—' : result.base.roi.toFixed(1) + '%') + ' · Net profit/acre: ' + money(result.base.perAcre) + ' · Break-even price/kg: ' + money(result.breakEvenPrice) + ' · Break-even harvested yield: ' + (result.breakEvenYield === null ? '—' : result.breakEvenYield.toFixed(2) + ' kg');
    } catch (error) { document.getElementById('estimateError').textContent = error.message; }
  });
  document.querySelectorAll('[data-asking-price]').forEach(button => button.addEventListener('click', () => { form.elements.price.value = button.dataset.askingPrice; }));
});
