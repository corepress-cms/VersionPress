/// <reference path='../components/search/Search.d.ts' />

import { action, observable, makeObservable } from 'mobx';

class SearchStore {
  config: SearchConfig = {};

  setConfig = (config: SearchConfig) => {
    this.config = config;
  };

  constructor() {
    makeObservable(this, {
      config: observable,
      setConfig: action
    });
  }
}

const searchStore = new SearchStore();

export { SearchStore };
export default searchStore;
