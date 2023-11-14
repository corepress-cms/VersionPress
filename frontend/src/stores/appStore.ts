/// <reference path='../components/common/Commits.d.ts' />

import { action, computed, observable, makeObservable } from 'mobx';
import { History } from 'history';

import { checkUpdate } from '../actions';
import { parsePageNumber } from '../actions/utils';

class AppStore {

  page: number = 0;
  selectedCommits: Commit[] = [];
  lastSelectedCommit: Commit | null = null;
  displayWelcomePanel: boolean = false;
  displayUpdateNotice: boolean = false;
  isDirtyWorkingDirectory: boolean = false;

  appHistory: History;

  refreshInterval: number;

  constructor() {
    makeObservable(this, {
      page: observable,
      selectedCommits: observable,
      lastSelectedCommit: observable,
      displayWelcomePanel: observable,
      displayUpdateNotice: observable,
      isDirtyWorkingDirectory: observable,
      enableActions: computed,
      setPage: action,
      setDisplayUpdateNotice: action,
      setDisplayWelcomePanel: action,
      setDirtyWorkingDirectory: action,
      setLastSelectedCommit: action,
      setSelectedCommits: action
    });

    this.refreshInterval = window.setInterval(() => checkUpdate(), 10 * 1000);
  }

  get enableActions() {
    return !this.isDirtyWorkingDirectory;
  }

  setPage = (page: number | string) => {
    this.page = parsePageNumber(page);
  };

  setDisplayUpdateNotice = (displayUpdateNotice: boolean) => {
    this.displayUpdateNotice = displayUpdateNotice;
  };

  setDisplayWelcomePanel = (displayWelcomePanel: boolean) => {
    this.displayWelcomePanel = displayWelcomePanel;
  };

  setDirtyWorkingDirectory = (isDirtyWorkingDirectory: boolean) => {
    this.isDirtyWorkingDirectory = isDirtyWorkingDirectory;
  };

  setLastSelectedCommit = (lastSelectedCommit: Commit | null) => {
    this.lastSelectedCommit = lastSelectedCommit;
  };

  setSelectedCommits = (selectedCommits: Commit[]) => {
    this.selectedCommits = selectedCommits;
  };

}

const appStore = new AppStore();

export { AppStore };
export default appStore;
