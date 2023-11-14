/// <reference path='../services/VpApi.d.ts' />

import { action, observable, makeObservable } from 'mobx';

import DetailsLevel from '../enums/DetailsLevel';

class CommitPanelStore {
  detailsLevel: DetailsLevel = DetailsLevel.None;
  diff?: string = undefined;
  gitStatus?: VpApi.GetGitStatusResponse = undefined;
  error?: string = undefined;
  isLoading: boolean = false;

  constructor() {
    makeObservable(this, {
      detailsLevel: observable,
      diff: observable,
      gitStatus: observable,
      error: observable,
      isLoading: observable,
      setDetailsLevel: action,
      setError: action,
      setLoading: action,
      setGitStatus: action,
      setDiff: action
    });
  }

  get hash() {
    return '';
  }

  setDetailsLevel = (detailsLevel: DetailsLevel) => {
    this.detailsLevel = detailsLevel;
  };

  setError = (error: string) => {
    this.error = error;
  };

  setLoading = (isLoading: boolean) => {
    this.isLoading = isLoading;
  };

  setGitStatus(gitStatus: VpApi.GetGitStatusResponse) {
    this.gitStatus = gitStatus;
  }

  setDiff(diff: string) {
    this.diff = diff;
  }
}

const commitPanelStore = new CommitPanelStore();

export { CommitPanelStore };
export default commitPanelStore;
