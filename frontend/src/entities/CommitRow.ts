import { action, computed, observable, makeObservable } from 'mobx';

import DetailsLevel from '../enums/DetailsLevel';

class CommitRow {

  commit: Commit;
  isSelected: boolean = false;
  detailsLevel: DetailsLevel = DetailsLevel.None;
  diff: string = "";
  error: string = "";
  isLoading: boolean = false;
  visualisation: Visualisation | null = null;

  constructor(commit: Commit, isSelected: boolean = false) {
    this.commit = commit;
    this.isSelected = isSelected;
    makeObservable(this, {
      commit: observable,
      isSelected: observable,
      detailsLevel: observable,
      diff: observable,
      error: observable,
      isLoading: observable,
      visualisation: observable,
      hash: computed,
      setDetailsLevel: action,
      setError: action,
      setLoading: action,
      setDiff: action,
      setVisualisation: action
    });
  }

  get hash() {
    return this.commit.hash;
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

  setDiff = (diff: string) => {
    this.diff = diff;
  };

  setVisualisation = (visualisation: Visualisation) => {
    this.visualisation = visualisation;
  };

}

export default CommitRow;
