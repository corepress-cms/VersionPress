import * as React from 'react';
import { createRoot } from 'react-dom/client';
import { HashRouter } from 'react-router-dom';
import { Provider } from 'mobx-react';

import * as stores from './stores';

import App from './components/app/App';


const root = createRoot(document.getElementById('vp')!);

root.render(
  <Provider {...stores}>
    <HashRouter>
      <App />
     </HashRouter>
  </Provider>
);
