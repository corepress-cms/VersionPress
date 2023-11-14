/// <reference path='./VersionPressConfig.d.ts' />

import merge from 'lodash/merge';
import localConfig from './config.local';
import window from './window';

const defaultConfig: VersionPressConfig = {
  api: {
    root: '',
    adminUrl: '',
    urlPrefix: 'wp-json',
    queryParam: 'rest_route',
    permalinkStructure: false,
    nonce: undefined,
  },
  routes: {
    page: 'page',
    home: '/',
  },
};

const vpApiConfig = {
  api: window.VP_API_Config || {},
};

let config = <VersionPressConfig> merge(defaultConfig, localConfig, vpApiConfig);

export default config;
