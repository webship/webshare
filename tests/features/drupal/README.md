# Drupal Standard feature suite

webship-js BDD scenarios that exercise the Webshare module on the **Drupal
Standard** install profile (Olivero theme). Run via:

```bash
LAUNCH_URL=http://<site>:<port> npx cucumber-js --config cucumber.js
```

The matching `cucumber.js` at the project root loads only this folder
(`paths: ['tests/features/drupal/**/*.feature']`) and routes reports /
screenshots / videos into `tests/{reports,screenshots,videos}/drupal/`.

Files use the **`NN-NN-NN-name.feature`** numbering convention shared
with the sibling Webship modules (webpage / webblog / webseo):

| Range | Purpose |
| --- | --- |
| `01-NN-NN` | Setup + smoke |
| `02-NN-NN` | Front-end rendering, platforms, a11y, responsive |
| `03-NN-NN` | Back-end administration |
| `04-NN-NN` | Access control |
| `05-NN-NN` | Drupal Canvas integration |

Every assertion uses a **named selector** from
`tests/selectors/{webshare,drupal-olivero}.json` via the
`Then the "<name>" element should …` step. No raw CSS appears in the
scenario lines.
