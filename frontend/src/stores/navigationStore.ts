/// <reference path='../components/common/Commits.d.ts' />

import { action, computed, observable, makeObservable } from 'mobx';

import appStore from './appStore';

class NavigationStore {
  activeQuery: string = '';
  query: string = '';

  constructor() {
    makeObservable(this, {
      activeQuery: observable,
      query: observable,
      changesCount: computed,
      hashes: computed,
      changeFilterQuery: action,
      changeActiveQuery: action
    });
  }

  get changesCount() {
    return appStore.selectedCommits.length;
  }

  get hashes() {
    return appStore.selectedCommits.map((commit: Commit) => commit.hash);
  }

  changeFilterQuery = (query: string) => {
    this.query = query;
  };

  changeActiveQuery = (activeQuery: string) => {
    this.activeQuery = activeQuery;
  };
}

const navigationStore = new NavigationStore();

export { NavigationStore };
export default navigationStore;
