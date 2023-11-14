/// <reference path='../interfaces/State.d.ts' />

import { action, observable, makeObservable } from 'mobx';

class ServicePanelStore {
  message: InfoMessage | null = null;
  isVisible: boolean = false;

  setMessage = (message: InfoMessage | null) => {
    this.message = message;
  };

  toggleVisibility = (isVisible?: boolean) => {
    this.isVisible = typeof isVisible === 'boolean' ? isVisible : !this.isVisible;
  };

  constructor() {
    makeObservable(this, {
      message: observable,
      isVisible: observable,
      setMessage: action,
      toggleVisibility: action
    });
  }
}

const servicePanelStore = new ServicePanelStore();

export { ServicePanelStore };
export default servicePanelStore;
