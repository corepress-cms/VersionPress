import * as React from 'react';
import { inject, observer } from 'mobx-react';
import { Route, Routes, HashRouter } from 'react-router-dom';

import HomePage from '../home/HomePage';
import NotFoundPage from '../not-found/NotFoundPage';

import { AppStore } from '../../stores/appStore';

import './App.less';

interface AppProps {
  appStore?: AppStore;
}

class App extends React.Component<AppProps, {}> {
  render() {
    return (
      <Routes>
        <Route path='/'  element={<HomePage />} />
        <Route path='/page/:page' element={<HomePage />} />
        <Route path='*'element={<NotFoundPage />} />
      </Routes>
    );
  }
}

export default inject('appStore')(observer(App));
