import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
  static targets = ['form', 'packageList'];
  static values = {
    packageUrl: String,
  };

  formTarget: HTMLFormElement;
  packageListTarget: HTMLElement;
  packageUrlValue: string;

  submitForm(event: Event) {
    event.preventDefault();

    const formData = new FormData(this.formTarget);

    const data = [];
    for (const [key, value] of formData) {
      data.push(`${encodeURIComponent(key)}=${encodeURIComponent(value.toString())}`);
    }
    const body = data.join("&");

    fetch(this.formTarget.getAttribute('action'), {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body,
    })
      .then(response => response.json())
      .then((data: PackageResultResponse) => {
        for (const result of data.results) {
          const packageUrl = this.packageUrlValue.replace('place/holder', result.packageName);

          const packageName = !result.error ? `<a href="${packageUrl}" target="_blank">${result.packageName}</a>` : result.packageName;
          const registryName = result.registryName ? result.registryName : '';
          let message = result.message;
          if (result.error) {
            message = `<div class="text-warning">${message}</div>`;
          } else if (result.created) {
            message = `<div class="text-success">${message}</div>`;
          }

          const listItem = document.createElement('div');
          listItem.innerHTML = `
            <div class="row gap-md-2 my-2">
              <div class="col-md-3 col-lg-2">${packageName}</div>
              <div class="col-md-3 col-lg-2">${registryName}</div>
              <div class="col-md-6 col-lg-8">${message}</div>
            </div>
          `;

          this.packageListTarget.appendChild(listItem);
        }
      });

    const packagesInput: HTMLFormElement = this.formTarget.querySelector('[name="package_add_mirroring_form[packages]"]');
    packagesInput.value = '';
  }
}

interface PackageResultResponse {
  results: PackageResult[];
}

interface PackageResult {
  packageName: string;
  registryName: string;
  message: string;
  created: boolean;
  error: boolean;
}
