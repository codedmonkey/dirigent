import { Controller } from '@hotwired/stimulus';
import Chart from 'chart.js/auto'; // todo limit size https://www.chartjs.org/docs/latest/getting-started/integration.html#bundle-optimization

/* stimulusFetch: 'lazy' */
export default class extends Controller {
  static targets = [
    'canvas',
  ]

  static values = {
    data: Object,
    type: String,
  }

  connect() {
    const data = this.dataValue;
    const type = this.typeValue;

    new Chart(
      this.canvasTarget,
      {
        type: 'line',
        data: this.parseData(data, type),
      },
    );
  }

  parseData(data, type) {
    switch (type) {
      case 'daily':
        return {
          labels: Object.keys(data).map(key => `${key.substring(0, 4)}-${key.substring(4, 6)}-${key.substring(6, 8)}`),
          datasets: [
            {
              label: 'Installations per day',
              data: Object.values(data),
            },
          ],
        };

      case 'daily-versions':
        const datasets = [];

        for (const key in data) {
          datasets.push({
            label: key,
            data: Object.values(data[key]),
          });
        }

        return {
          labels: Object.keys(data[0]).map(key => `${key.substring(0, 4)}-${key.substring(4, 6)}-${key.substring(6, 8)}`),
          datasets,
        };
    }
  }
}
